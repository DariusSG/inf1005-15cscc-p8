<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class UserControllerApiTest extends TestCase
{
    public function test_show_returns_user_payload_as_json(): void
    {
        [$exitCode, $output] = $this->runShowRequest(
            <<<'PHP'
public function getUserById(int $id): array
{
    return [
        'id' => $id,
        'email' => 'student@example.com',
        'name' => 'Student User',
        'role' => 'user',
    ];
}
PHP,
            7
        );

        $this->assertSame(0, $exitCode);
        $this->assertSame('{"id":7,"email":"student@example.com","name":"Student User","role":"user"}', $output);
    }

    public function test_show_returns_error_payload_when_user_not_found(): void
    {
        [$exitCode, $output] = $this->runShowRequest(
            <<<'PHP'
public function getUserById(int $id): array
{
    throw new \Exception('User not found');
}
PHP,
            9999
        );

        $this->assertSame(0, $exitCode);
        $this->assertSame('{"error":"User not found"}', $output);
    }

    private function runShowRequest(string $userServiceMethod, int $id): array
    {
        $autoloadPath = realpath(__DIR__ . '/../../vendor/autoload.php');
        $script = <<<PHP
<?php

require '{$autoloadPath}';

use App\\Controllers\\UserController;
use App\\Core\\Container;

Container::bind('UserService', fn () => new class {
{$userServiceMethod}
});

(new UserController())->show({$id});
PHP;

        $tempScript = tempnam(sys_get_temp_dir(), 'user-api-test-');
        file_put_contents($tempScript, $script);

        $output = [];
        $exitCode = 1;
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tempScript), $output, $exitCode);

        @unlink($tempScript);

        return [$exitCode, implode("\n", $output)];
    }
}