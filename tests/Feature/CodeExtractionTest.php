<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Services\Guard\CodeExtractor;
use App\Services\Guard\EmailCodeFetcher;
use App\Services\Guard\Totp;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Gerçek platform maillerinin metnine benzetilmiş örneklerle kod çıkarma.
 * Mailler elle kısaltıldı; alt bilgideki posta kodu, yıl ve telefon gibi
 * tuzaklar bilerek bırakıldı.
 */
class CodeExtractionTest extends TestCase
{
    private function extract(string $platform, string $subject, string $text): ?array
    {
        $account = new Account(['platform' => $platform]);

        return app(CodeExtractor::class)->extract($subject, $text, $account->codeFormatConfig(), $account->codeAnchors());
    }

    public static function samples(): array
    {
        return [
            'chatgpt' => ['chatgpt', 'Your ChatGPT code is 482913',
                'Enter this temporary verification code to continue: 482913 If you were not trying to log in to ChatGPT, please reset your password. OpenAI 1455 3rd Street San Francisco, CA 94158', '482913'],
            'chatgpt sadece gövde' => ['chatgpt', 'ChatGPT log-in code',
                'Hi, Nov 25 2026. Your ChatGPT code is 730115. This code expires in 10 minutes.', '730115'],
            'netflix giriş kodu, yıl tuzağı' => ['netflix', 'Netflix: Your sign-in code',
                'Hi, Nov 25, 2026 Enter this code to sign in 4829 This code will expire in 15 minutes. Netflix Services 2026 Los Gatos CA 95032', '4829'],
            'netflix türkçe' => ['netflix', 'Netflix: Oturum açma kodunuz',
                '© 2026 Netflix. Oturum açmak için bu kodu girin. Oturum açma kodu 0517 Kodun süresi 15 dakika içinde dolacak.', '0517'],
            'netflix hane geçici erişim' => ['netflix', 'Netflix temporary access code',
                'Your temporary access code is 3391. Valid for 15 minutes. Questions? Call 0-800-123-4567', '3391'],
            'steam guard maili' => ['steam', 'Your Steam account: Access from new web or mobile device',
                'Dear gamer123, Here is the Steam Guard code you need to login to account gamer123: Login Code F7K2Q If you are not attempting to login then please change your Steam password, and consider changing your email password as well. Valve Corporation PO Box 1688 Bellevue, WA 98009', 'F7K2Q'],
            'steam türkçe' => ['steam', 'Steam hesabınız: Yeni bir cihazdan erişim',
                'Merhaba gamer123, gamer123 hesabına giriş yapmak için Steam Guard kodu: Giriş Kodu 9HDRT Steam Destek', '9HDRT'],
            'ea security code' => ['ea', 'Your EA Security Code is: 551204',
                'Here is your security code. Your EA Security Code: 551204 If this email was unexpected, please contact us. The EA Team', '551204'],
            'epic security code' => ['epic', 'Your Epic Games security code',
                'Hello, Here is your security code: 739104 This code will expire in 30 minutes. Epic Games, Inc. 620 Crossroads Blvd. Cary, NC 27518', '739104'],
            'ubisoft verification' => ['ubisoft', 'Ubisoft Account - Verification code',
                'Your verification code is: 552901 Ubisoft Entertainment, 2 rue du Chêne Heleuc 56910', '552901'],
            'riot' => ['riot', 'Riot Account Sign-in Code',
                'Your sign-in code: 600211 Riot Games, 12333 W Olympic Blvd', '600211'],
            'battle.net' => ['battlenet', 'Battle.net Security Code',
                'Greetings, Use the following security code to log in to your Battle.net account: 418302 This code expires in 10 minutes. Blizzard Entertainment, P.O. Box 18979, Irvine, CA 92623', '418302'],
            'microsoft güvenlik kodu' => ['xbox', 'Microsoft account security code',
                'Please use the following security code for the Microsoft account ga*****@gmail.com. Security code: 907143 If you don\'t recognize the Microsoft account ga*****@gmail.com, you can click here to remove your email from that account. Thanks, The Microsoft account team', '907143'],
            'microsoft 7 haneli' => ['xbox', 'Your single-use code',
                'Hi ga*****@gmail.com, We received your request for a single-use code to use with your Microsoft account. Your single-use code is: 2849173', '2849173'],
            'playstation' => ['playstation', 'PlayStation sign-in verification',
                'Enter this verification code to sign in to your account: 664201 The code is valid for 10 minutes. Sony Interactive Entertainment LLC, 2207 Bridgepointe Pkwy, San Mateo, CA 94404', '664201'],
            'nintendo 4 hane, adres tuzağı' => ['nintendo', '[Nintendo Account] Identity verification',
                'Nintendo Account identity verification. Enter the following verification code: 7302 Nintendo of America Inc. 4600 150th Avenue NE Redmond, WA', '7302'],
            'nintendo 6 hane' => ['nintendo', 'Nintendo Account', 'Your verification code is 590214. © 2026 Nintendo', '590214'],
            'boşluklu 6 rakam' => ['epic', 'Epic Games', 'Your security code: 739 104 Thanks', '739104'],
        ];
    }

    #[DataProvider('samples')]
    public function test_platform_mail_samples(string $platform, string $subject, string $text, string $expected): void
    {
        $found = $this->extract($platform, $subject, $text);

        $this->assertNotNull($found, 'kod bulunamadı');
        $this->assertSame($expected, $found['code']);
        $this->assertFalse($found['blocked']);
    }

    public static function blockedSamples(): array
    {
        return [
            'ea e-posta değişikliği' => ['ea', 'Your EA Security Code is: 551204',
                'You requested to change your email address. Your EA Security Code: 551204 If this email was unexpected, please contact us. The EA Team'],
            'steam hesap değişikliği' => ['steam', 'Steam Account Change Request',
                'Here is the code you need to change the email on account gamer123: F7K2Q'],
            'steam guard kaldırma' => ['steam', 'Your Steam account',
                'Here is the code you need for removing the Steam Guard Mobile Authenticator: Login Code F7K2Q'],
            'şifre sıfırlama' => ['netflix', 'Netflix: Şifreni sıfırla', 'Şifreni sıfırlamak için kod 4829'],
            'battle.net doğrulayıcı kaldırma' => ['battlenet', 'Battle.net Account',
                'You requested to remove the Battle.net Authenticator from your account. Security code: 418302'],
            'microsoft güvenlik bilgisi' => ['xbox', 'Verify your security info',
                'Please use the following security code to verify your security info. Security code: 907143'],
            'playstation sign-in id değişikliği' => ['playstation', 'Verification code',
                'You requested to change your sign-in ID. Verification code: 664201'],
            'chatgpt e-posta değiştirme' => ['chatgpt', 'Verify your new email', 'To change your email address enter this verification code: 482913'],
        ];
    }

    #[DataProvider('blockedSamples')]
    public function test_account_takeover_mails_are_blocked(string $platform, string $subject, string $text): void
    {
        $found = $this->extract($platform, $subject, $text);

        $this->assertNotNull($found);
        $this->assertTrue($found['blocked']);
    }

    public function test_footer_numbers_are_not_taken_as_codes(): void
    {
        $this->assertNull($this->extract('netflix', 'Netflix news', 'New on Netflix in 2026. Netflix, Los Gatos'));
        $this->assertNull($this->extract('steam', 'Steam sale', 'STEAM WINTER SALE starts now. Valve PO Box 1688 WA 98009'));
    }

    public function test_link_format_returns_household_link(): void
    {
        $account = new Account(['platform' => 'netflix', 'code_format' => 'netflix_link']);
        $found = app(CodeExtractor::class)->extract('Update your Netflix Household', 'Yes, this was me https://www.netflix.com/account/update-primary-location?nftoken=abc&amp;x=1 Help', $account->codeFormatConfig());

        $this->assertSame('https://www.netflix.com/account/update-primary-location?nftoken=abc&x=1', $found['code']);
    }

    public function test_html_body_is_flattened_without_css_colors(): void
    {
        $text = app(EmailCodeFetcher::class)->plainText('', '<html><head><style>.a{color:#123456}</style></head><body><p style="color:#654321">Your code</p><td>482913</td><a href="https://x.test/a">x</a></body></html>');

        $this->assertStringNotContainsString('123456', $text);
        $this->assertStringNotContainsString('654321', $text);
        $this->assertStringContainsString('482913', $text);
        $this->assertStringContainsString('https://x.test/a', $text);
    }

    public function test_pick_prefers_newest_mail_from_platform_and_respects_lookback(): void
    {
        $account = new Account(['platform' => 'chatgpt']);
        $mail = fn ($from, $subject, $minutes, $folder = 'INBOX') => [
            'from' => $from, 'subject' => $subject, 'text' => '', 'received_at' => Carbon::now()->subMinutes($minutes), 'folder' => $folder,
        ];

        $found = app(EmailCodeFetcher::class)->pick([
            $mail('noreply@tm.openai.com', 'Your ChatGPT code is 111222', 5),
            $mail('spam@example.com', 'Your ChatGPT code is 999888', 1),
            $mail('otp@tm1.openai.com', 'Your ChatGPT code is 333444', 2, '[Gmail]/Spam'),
            $mail('noreply@tm.openai.com', 'Your ChatGPT code is 555666', 90),
        ], $account);

        $this->assertSame('333444', $found['code']);
        $this->assertSame('[Gmail]/Spam', $found['folder']);
    }

    public function test_newest_takeover_mail_wins_over_older_login_code(): void
    {
        $account = new Account(['platform' => 'ea']);
        $found = app(EmailCodeFetcher::class)->pick([
            ['from' => 'ea@e.ea.com', 'subject' => 'Your EA Security Code is: 100200', 'text' => 'Your EA Security Code: 100200', 'received_at' => Carbon::now()->subMinutes(6), 'folder' => 'INBOX'],
            ['from' => 'ea@e.ea.com', 'subject' => 'Your EA Security Code is: 300400', 'text' => 'You asked to update your email address. Your EA Security Code: 300400', 'received_at' => Carbon::now()->subMinute(), 'folder' => 'INBOX'],
        ], $account);

        $this->assertTrue($found['blocked']);
    }

    public function test_custom_sender_filter_accepts_several_parts(): void
    {
        $account = new Account(['platform' => 'other', 'mail_from' => 'a.com, b.com']);

        $this->assertSame(['a.com', 'b.com'], $account->senders());
        $this->assertSame(['ubisoft.com', 'ubi.com'], (new Account(['platform' => 'ubisoft']))->senders());
    }

    public function test_steam_guard_codes_match_reference_implementation(): void
    {
        // Değerler Node.js ile bağımsız bir uygulamadan (steam-totp algoritması) alındı.
        $secret = 'steam:'.base64_encode('12345678901234567890');
        $totp = new Totp;

        $this->assertSame('PV9M4', $totp->code($secret, 59));
        $this->assertSame('PY4YB', $totp->code($secret, 1111111109));
        $this->assertSame('R87JJ', $totp->code($secret, 1700000000));
        $this->assertSame(5, Totp::length($secret));
    }

    public function test_mafile_and_shared_secret_are_accepted_for_steam(): void
    {
        $shared = base64_encode('12345678901234567890');
        $mafile = json_encode(['shared_secret' => $shared, 'account_name' => 'gamer123', 'identity_secret' => 'x']);

        $this->assertSame('steam:'.$shared, Totp::cleanSecret($mafile));
        $this->assertSame('steam:'.$shared, Totp::cleanSecret($shared, steam: true));
        $this->assertSame('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', Totp::cleanSecret('gezd gnbv gy3t qojq gezd gnbv gy3t qojq', steam: true));

        $this->expectException(InvalidArgumentException::class);
        Totp::cleanSecret('{"account_name":"x"}');
    }
}
