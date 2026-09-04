<?php

namespace Tests\Feature;

use App\Models\DatabaseConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConnectionCredentialSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_connection_password_is_encrypted_at_rest(): void
    {
        $connection = DatabaseConnection::query()->create([
            'name' => 'Credential Test',
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'credential_test',
            'username' => 'security_user',
            'password' => 'super-secret-password',
        ]);

        $rawPassword = DB::table('database_connections')
            ->where('id', $connection->id)
            ->value('password');

        $this->assertNotNull($rawPassword);
        $this->assertNotSame(
            'super-secret-password',
            $rawPassword
        );

        $this->assertSame(
            'super-secret-password',
            Crypt::decryptString($rawPassword)
        );

        $this->assertSame(
            'super-secret-password',
            $connection->getDecryptedPassword()
        );
    }

    public function test_database_connection_password_is_hidden_from_serialization(): void
    {
        $connection = DatabaseConnection::query()->create([
            'name' => 'Serialization Test',
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'serialization_test',
            'username' => 'security_user',
            'password' => 'hidden-secret',
        ]);

        $serialized = $connection->fresh()->toArray();

        $this->assertArrayNotHasKey(
            'password',
            $serialized
        );

        $this->assertStringNotContainsString(
            'hidden-secret',
            json_encode($serialized, JSON_THROW_ON_ERROR)
        );
    }

    public function test_null_database_password_remains_null(): void
    {
        $connection = DatabaseConnection::query()->create([
            'name' => 'Passwordless Test',
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'passwordless_test',
            'username' => 'security_user',
            'password' => null,
        ]);

        $rawPassword = DB::table('database_connections')
            ->where('id', $connection->id)
            ->value('password');

        $this->assertNull($rawPassword);
        $this->assertNull(
            $connection->getDecryptedPassword()
        );
    }

    public function test_updating_database_password_reencrypts_new_value(): void
    {
        $connection = DatabaseConnection::query()->create([
            'name' => 'Rotation Test',
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'rotation_test',
            'username' => 'security_user',
            'password' => 'old-secret',
        ]);

        $oldCiphertext = DB::table('database_connections')
            ->where('id', $connection->id)
            ->value('password');

        $connection->update([
            'password' => 'new-secret',
        ]);

        $newCiphertext = DB::table('database_connections')
            ->where('id', $connection->id)
            ->value('password');

        $this->assertNotSame(
            $oldCiphertext,
            $newCiphertext
        );

        $this->assertSame(
            'new-secret',
            Crypt::decryptString($newCiphertext)
        );
    }
}
