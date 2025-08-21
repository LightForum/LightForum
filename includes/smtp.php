<?php
/**
 * 发送邮件
 * 
 * @param string $to 收件人邮箱
 * @param string $subject 邮件主题
 * @param string $message 邮件内容（HTML格式）
 * @return bool 是否发送成功
 */
function sendEmail($to, $subject, $message) {
    try {
        // 获取数据库连接和表前缀
        $db = Database::getInstance();
        $prefix = defined('DB_PREFIX') ? DB_PREFIX : 'forum_';
        
        // 获取SMTP设置
        $settings = $db->fetchAll("SELECT `setting_key`, `setting_value` FROM `{$prefix}settings` WHERE `setting_key` LIKE 'smtp_%'");
        $smtpSettings = [];
        
        foreach ($settings as $setting) {
            $smtpSettings[$setting['setting_key']] = $setting['setting_value'];
        }
        
        // 检查是否有SMTP设置
        if (empty($smtpSettings['smtp_host']) || empty($smtpSettings['smtp_port']) || 
            empty($smtpSettings['smtp_username']) || empty($smtpSettings['smtp_from'])) {
            error_log('无法发送邮件：SMTP设置不完整');
            return false;
        }
        
        // 加载PHPMailer
        require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/src/SMTP.php';
        require_once __DIR__ . '/PHPMailer/src/Exception.php';
        
        // 创建邮件实例
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // 服务器设置
            $mail->SMTPDebug = 0;  // 调试模式（0=关闭，2=详细）
            $mail->isSMTP();       // 使用SMTP
            $mail->Host = $smtpSettings['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtpSettings['smtp_username'];
            $mail->Password = $smtpSettings['smtp_password'];
            
            if (!empty($smtpSettings['smtp_secure'])) {
                $mail->SMTPSecure = $smtpSettings['smtp_secure'];
            }
            
            $mail->Port = (int)$smtpSettings['smtp_port'];
            
            // 发件人
            $mail->setFrom($smtpSettings['smtp_from'], $smtpSettings['smtp_from_name']);
            
            // 收件人
            $mail->addAddress($to);
            
            // 设置字符编码
            $mail->CharSet = 'UTF-8';
            
            // 内容
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message);
;
            // 发送邮件
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('邮件发送失败: ' . $mail->ErrorInfo);
            return false;
        }
        
    } catch (Exception $e) {
        error_log('获取SMTP设置失败: ' . $e->getMessage());
        return false;
    }
}