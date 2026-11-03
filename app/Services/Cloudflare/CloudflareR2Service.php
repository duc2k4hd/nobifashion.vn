<?php

namespace App\Services\Cloudflare;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CloudflareR2Service
{
    protected string $accountId;
    protected string $accessKeyId;
    protected string $secretAccessKey;
    protected string $bucketName;
    protected string $publicUrl;
    protected ?string $apiToken;
    protected string $s3Host;

    public function __construct()
    {
        $this->accountId = env('CLOUDFLARE_ACCOUNT_ID', 'ea42a289e565f25b3434383f4323549b');
        $this->accessKeyId = (string) env('CLOUDFLARE_R2_ACCESS_KEY_ID', '27ec34b9a6efbdaf3662bd0aaa878db5');
        $this->secretAccessKey = (string) env('CLOUDFLARE_R2_SECRET_ACCESS_KEY', '350d15a39358bbb78f8c963408715c279c688b552e66eb2aec73459eb5a4963a');
        $this->bucketName = (string) env('CLOUDFLARE_R2_BUCKET_NAME', 'nobifashion-reels');
        $this->publicUrl = rtrim((string) env('CLOUDFLARE_R2_PUBLIC_URL', 'https://media.nobifashion.vn'), '/');
        $this->apiToken = env('CLOUDFLARE_API_TOKEN', 'cfat_9GbVFk6aeVpnP7sJjyX3gkpYaSHc2IY8FzJCMFTv00462c14');
        $this->s3Host = "{$this->accountId}.r2.cloudflarestorage.com";
    }

    /**
     * Lấy thông tin thống kê dung lượng lưu trữ và số lượng tệp từ Cloudflare REST API
     */
    public function getStorageMetrics(): array
    {
        if (empty($this->apiToken)) {
            return [
                'success' => false,
                'message' => 'Chưa cấu hình CLOUDFLARE_API_TOKEN',
            ];
        }

        try {
            $url = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/r2/buckets/{$this->bucketName}/usage";
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                $data = $response->json('result') ?? [];
                $payloadBytes = (int) ($data['payloadSize'] ?? 0);
                $objectCount = (int) ($data['objectCount'] ?? 0);

                return [
                    'success' => true,
                    'object_count' => $objectCount,
                    'payload_bytes' => $payloadBytes,
                    'payload_formatted' => $this->formatBytes($payloadBytes),
                    'bucket_name' => $this->bucketName,
                    'account_id' => $this->accountId,
                    'public_url' => $this->publicUrl,
                ];
            }
        } catch (\Throwable $e) {
            // Không ngắt luồng nếu API usage gặp lỗi mạng
        }

        return [
            'success' => false,
            'object_count' => 0,
            'payload_bytes' => 0,
            'payload_formatted' => '0 MB',
            'bucket_name' => $this->bucketName,
            'account_id' => $this->accountId,
            'public_url' => $this->publicUrl,
        ];
    }

    /**
     * Liệt kê danh sách Objects trên Cloudflare R2 qua S3 API với phân trang
     */
    public function listObjects(string $prefix = '', ?string $continuationToken = null, int $maxKeys = 300): array
    {
        $queryArr = [
            'list-type' => '2',
            'max-keys' => (string) $maxKeys,
        ];

        if (!empty($prefix)) {
            $queryArr['prefix'] = $prefix;
        }

        if (!empty($continuationToken)) {
            $queryArr['continuation-token'] = $continuationToken;
        }

        ksort($queryArr);
        $queryString = http_build_query($queryArr, '', '&', PHP_QUERY_RFC3986);

        $path = "/{$this->bucketName}";
        $headers = $this->generateAwsV4Headers('GET', $path, $queryString, '');

        $url = "https://{$this->s3Host}{$path}?" . $queryString;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $rawXml = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($rawXml)) {
            return [
                'success' => false,
                'items' => [],
                'next_token' => null,
                'is_truncated' => false,
                'error' => "S3 API Error (HTTP {$httpCode})",
            ];
        }

        $xml = @simplexml_load_string($rawXml);
        if ($xml === false) {
            return [
                'success' => false,
                'items' => [],
                'next_token' => null,
                'is_truncated' => false,
                'error' => 'Không thể phân tích dữ liệu XML từ Cloudflare R2',
            ];
        }

        $items = [];
        if (isset($xml->Contents)) {
            foreach ($xml->Contents as $content) {
                $key = (string) $content->Key;
                $size = (int) $content->Size;

                // Bỏ qua các key dạng thư mục ảo (dung lượng 0 và kết thúc bằng /)
                if (str_ends_with($key, '/') && $size === 0) {
                    continue;
                }

                $extension = strtolower(pathinfo($key, PATHINFO_EXTENSION));
                $isVideo = in_array($extension, ['mp4', 'mov', 'webm', 'm4v', 'avi']);
                $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif']);

                $items[] = [
                    'key' => $key,
                    'file_name' => basename($key),
                    'size' => $size,
                    'size_formatted' => $this->formatBytes($size),
                    'last_modified' => (string) $content->LastModified,
                    'etag' => trim((string) $content->ETag, '"'),
                    'extension' => $extension,
                    'is_video' => $isVideo,
                    'is_image' => $isImage,
                    'public_url' => $this->getPublicUrl($key),
                ];
            }
        }

        $isTruncated = ((string) $xml->IsTruncated) === 'true';
        $nextToken = isset($xml->NextContinuationToken) ? (string) $xml->NextContinuationToken : null;

        return [
            'success' => true,
            'items' => $items,
            'is_truncated' => $isTruncated,
            'next_token' => $nextToken,
            'total_in_batch' => count($items),
        ];
    }

    /**
     * Tải tệp lên Cloudflare R2 qua S3 PUT Object
     */
    public function uploadObject(string $key, string $fileContent, string $mimeType = 'video/mp4'): array
    {
        $path = "/{$this->bucketName}/" . ltrim($key, '/');
        $payloadHash = hash('sha256', $fileContent);

        $headers = $this->generateAwsV4Headers('PUT', $path, '', $payloadHash, [
            'content-type' => $mimeType,
        ]);

        $url = "https://{$this->s3Host}{$path}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $fileContent,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            return [
                'success' => true,
                'key' => $key,
                'public_url' => $this->getPublicUrl($key),
            ];
        }

        return [
            'success' => false,
            'error' => "Lỗi upload lên Cloudflare R2 (HTTP {$httpCode})",
        ];
    }

    /**
     * Xóa tệp từ xa trên Cloudflare R2 qua S3 DELETE Object
     */
    public function deleteObject(string $key): array
    {
        $path = "/{$this->bucketName}/" . ltrim($key, '/');
        $headers = $this->generateAwsV4Headers('DELETE', $path, '', hash('sha256', ''));

        $url = "https://{$this->s3Host}{$path}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 204 || $httpCode === 200) {
            return [
                'success' => true,
                'message' => 'Đã xóa tệp thành công trên Cloudflare R2',
            ];
        }

        return [
            'success' => false,
            'error' => "Lỗi xóa tệp trên Cloudflare R2 (HTTP {$httpCode})",
        ];
    }

    /**
     * Lấy URL công khai của Object trên R2
     */
    public function getPublicUrl(string $key): string
    {
        return $this->publicUrl . '/' . ltrim($key, '/');
    }

    /**
     * Tạo chữ ký chuẩn AWS Signature V4 cho mọi thao tác S3 API
     */
    protected function generateAwsV4Headers(
        string $method,
        string $canonicalUri,
        string $canonicalQueryString,
        string $payloadHash = '',
        array $extraHeaders = []
    ): array {
        $region = 'auto';
        $service = 's3';

        $datetime = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');

        if (empty($payloadHash)) {
            $payloadHash = hash('sha256', '');
        }

        $headers = [
            'host' => $this->s3Host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date' => $datetime,
        ];

        foreach ($extraHeaders as $k => $v) {
            $headers[strtolower($k)] = trim($v);
        }

        ksort($headers);

        $canonicalHeaders = '';
        $signedHeadersArr = [];
        $curlHeaders = [];

        foreach ($headers as $k => $v) {
            $canonicalHeaders .= $k . ':' . $v . "\n";
            $signedHeadersArr[] = $k;
            $curlHeaders[] = ucfirst($k) . ': ' . $v;
        }

        $signedHeaders = implode(';', $signedHeadersArr);

        // Canonical Request
        $canonicalRequest = "{$method}\n{$canonicalUri}\n{$canonicalQueryString}\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";

        // String to Sign
        $credentialScope = "{$date}/{$region}/{$service}/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$datetime}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);

        // Calculate Signature
        $kDate = hash_hmac('sha256', $date, 'AWS4' . $this->secretAccessKey, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authHeader = "Authorization: AWS4-HMAC-SHA256 Credential={$this->accessKeyId}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";
        $curlHeaders[] = $authHeader;

        return $curlHeaders;
    }

    /**
     * Định dạng số byte sang KB, MB, GB dễ đọc
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

