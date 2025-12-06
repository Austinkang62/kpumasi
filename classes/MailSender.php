<?php
/**
 * SMTP 이메일 발송 클래스
 */
class MailSender {
    private $config;

    public function __construct() {
        $this->config = require __DIR__ . '/../config/smtp.php';
    }

    /**
     * 이메일 발송
     * @param string $to 수신자 이메일
     * @param string $subject 제목
     * @param string $htmlMessage HTML 메시지
     * @return bool 성공 여부
     */
    public function send($to, $subject, $htmlMessage) {
        // 디버그 모드인 경우 실제 발송하지 않고 로그만 기록
        if ($this->config['debug_mode']) {
            error_log("=== EMAIL DEBUG MODE ===");
            error_log("To: {$to}");
            error_log("Subject: {$subject}");
            error_log("Message: " . strip_tags($htmlMessage));
            error_log("=======================");
            return true;
        }

        // SMTP 발송 (PHPMailer 또는 직접 구현 필요)
        // 현재는 PHP mail() 함수 사용
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->config['from_name']} <{$this->config['from_email']}>\r\n";

        return mail($to, $subject, $htmlMessage, $headers);
    }

    /**
     * 인증 코드 이메일 발송
     * @param string $to 수신자 이메일
     * @param string $code 인증 코드
     * @return bool 성공 여부
     */
    public function sendVerificationCode($to, $code) {
        $subject = '[We One] 이메일 인증 코드';
        $message = "
            <html>
            <head>
                <style>
                    body { font-family: 'Arial', sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                    .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #D4AF37 0%, #F4E5C2 100%); padding: 40px 20px; text-align: center; }
                    .header h1 { margin: 0; color: #1a1a1a; font-size: 28px; font-weight: 700; }
                    .content { padding: 40px 30px; }
                    .code-box { background: #f8f9fa; border: 2px solid #D4AF37; border-radius: 8px; padding: 20px; text-align: center; margin: 30px 0; }
                    .code { font-size: 36px; font-weight: 700; letter-spacing: 8px; color: #D4AF37; }
                    .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>We One</h1>
                        <p style='color: #666; margin: 10px 0 0 0;'>Premium Network Platform</p>
                    </div>
                    <div class='content'>
                        <h2 style='color: #1a1a1a; margin-bottom: 20px;'>이메일 인증 코드</h2>
                        <p style='color: #666; line-height: 1.6;'>회원가입을 위한 이메일 인증 코드입니다.</p>
                        <p style='color: #666; line-height: 1.6;'>아래 코드를 회원가입 페이지에 입력해주세요.</p>
                        <div class='code-box'>
                            <div class='code'>{$code}</div>
                        </div>
                        <p style='color: #999; font-size: 14px;'>이 코드는 10분간 유효합니다.</p>
                        <p style='color: #999; font-size: 14px;'>본인이 요청하지 않았다면 이 메일을 무시하세요.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; 2024 We One. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        return $this->send($to, $subject, $message);
    }
}
