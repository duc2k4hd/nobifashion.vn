
<style>
    .policy-page {
        width: min(1100px, 92vw);
        margin: 40px auto 70px;
        font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif;
        color: #0c1627;
    }

    .policy-hero {
        position: relative;
        padding: 48px;
        border-radius: 36px;
        background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.2), transparent 40%),
            linear-gradient(135deg, #020611, #172950 60%, #0b162d);
        color: #f5fbff;
        overflow: hidden;
        box-shadow: 0 30px 60px rgba(8, 15, 35, 0.35);
    }

    .policy-hero h1 {
        font-size: clamp(34px, 4vw, 48px);
        margin-bottom: 12px;
    }

    .policy-hero p {
        color: rgba(255, 255, 255, 0.8);
        line-height: 1.7;
        margin-bottom: 24px;
    }

    .policy-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }

    .policy-tag {
        padding: 8px 16px;
        border-radius: 999px;
        font-size: 13px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        background: rgba(255, 255, 255, 0.15);
    }

    .policy-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 14px;
    }

    .policy-meta-card {
        border-radius: 20px;
        padding: 18px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        background: rgba(255, 255, 255, 0.08);
    }

    .policy-meta-card span {
        font-size: 13px;
        opacity: 0.7;
    }

    .policy-meta-card strong {
        display: block;
        margin-top: 6px;
        font-size: 22px;
    }

    .policy-content {
        margin-top: 32px;
        display: flex;
        flex-direction: column;
        gap: 26px;
    }

    .policy-section {
        padding: 28px 30px;
        margin: 10px 0;
        border-radius: 26px;
        background: #fff;
        border: 1px solid rgba(12, 22, 39, 0.08);
        box-shadow: 0 18px 40px rgba(6, 11, 25, 0.08);
    }

    .policy-section h2,
    .policy-section h3 {
        font-size: 20px;
        margin-bottom: 14px;
        color: #0e1b33;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .policy-section h2::before,
    .policy-section h3::before {
        content: '';
        width: 32px;
        height: 4px;
        border-radius: 999px;
        background: linear-gradient(90deg, #ff6f91, #ffc15e);
        display: inline-block;
    }

    .policy-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 18px;
        margin-top: 10px;
    }

    .policy-card {
        padding: 20px;
        border-radius: 20px;
        border: 1px solid rgba(12, 22, 39, 0.08);
        background: rgba(249, 250, 253, 0.9);
    }

    .policy-card strong {
        display: block;
        margin-bottom: 6px;
    }

    .policy-list {
        margin: 0;
        padding-left: 20px;
        color: #2e3a4e;
        line-height: 1.7;
    }

    .policy-note {
        padding: 16px 20px;
        border-radius: 16px;
        background: rgba(255, 153, 0, 0.1);
        border: 1px dashed rgba(255, 153, 0, 0.4);
        margin-top: 16px;
        color: #8a5600;
    }

    .policy-contact {
        border-radius: 26px;
        padding: 28px;
        background: linear-gradient(135deg, #fdf2ff, #e3f7ff);
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 18px 40px rgba(38, 24, 70, 0.08);
    }

    .policy-contact p {
        margin: 6px 0;
        font-weight: 600;
    }

    .policy-contact a {
        color: #ff4b6e;
        text-decoration: none;
        font-weight: 600;
    }

    .policy-timeline {
        margin-top: 12px;
        border-left: 2px solid rgba(12, 22, 39, 0.1);
        padding-left: 22px;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .policy-timeline-item {
        position: relative;
    }

    .policy-timeline-item::before {
        content: '';
        position: absolute;
        left: -30px;
        top: 6px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ff6f91, #ffc15e);
        box-shadow: 0 0 0 6px rgba(255, 153, 0, 0.18);
    }

    .policy-updated {
        margin-top: 24px;
        text-align: center;
        font-style: italic;
        color: #5f6b7c;
    }

    @media (max-width: 768px) {
        .policy-hero {
            padding: 32px 24px;
        }

        .policy-section {
            padding: 22px;
        }

        .policy-hero h1 {
            font-size: 32px;
        }
    }
</style>

