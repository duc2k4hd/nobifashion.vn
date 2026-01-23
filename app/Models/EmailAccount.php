<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'email',
        'name',
        'description',
        'is_default',
        'is_active',
        'order',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
        'mail_port' => 'integer',
    ];

    /**
     * Accessor: Decrypt password khi lấy ra
     */
    public function getMailPasswordAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    /**
     * Mutator: Encrypt password khi lưu vào
     */
    public function setMailPasswordAttribute($value)
    {
        $this->attributes['mail_password'] = $value ? encrypt($value) : null;
    }

    /**
     * Lấy cấu hình SMTP (dùng giá trị riêng hoặc fallback về .env)
     */
    public function getSmtpConfig(): array
    {
        return [
            'host' => $this->mail_host ?? config('mail.mailers.smtp.host'),
            'port' => $this->mail_port ?? config('mail.mailers.smtp.port'),
            'username' => $this->mail_username ?? config('mail.mailers.smtp.username'),
            'password' => $this->mail_password ?? config('mail.mailers.smtp.password'),
            'encryption' => $this->mail_encryption ?? config('mail.mailers.smtp.encryption'),
        ];
    }

    /**
     * Scope: Chỉ lấy email đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Lấy email mặc định
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope: Sắp xếp theo order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /**
     * Lấy email mặc định
     */
    public static function getDefault(): ?self
    {
        return static::active()->default()->first() 
            ?? static::active()->ordered()->first();
    }

    /**
     * Lấy tất cả email đang hoạt động
     */
    public static function getActiveEmails()
    {
        return static::active()->ordered()->get();
    }

    /**
     * Đặt email này làm mặc định (bỏ mặc định các email khác)
     */
    public function setAsDefault(): void
    {
        static::where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }
}
