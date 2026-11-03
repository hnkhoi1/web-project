<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use GdImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AvatarTest extends TestCase
{
    use RefreshDatabase;

    private const MIMES_MESSAGE = 'Ảnh đại diện phải là tệp JPG, PNG hoặc WebP.';

    private function encode(GdImage $img, string $fn = 'imagepng'): string
    {
        ob_start();
        $fn($img);

        return (string) ob_get_clean();
    }

    private function upload(string $bytes, string $name): UploadedFile
    {
        // A real UploadedFile (not the fake) so the mime type is sniffed from the bytes, not the name.
        $path = tempnam(sys_get_temp_dir(), 'avatar');
        file_put_contents($path, $bytes);

        return new UploadedFile($path, $name, null, null, true);
    }

    /** @return array{0: int, 1: int, 2: int} r, g, b */
    private function pixel(GdImage $img, int $x, int $y): array
    {
        $c = imagecolorat($img, $x, $y);

        return [($c >> 16) & 255, ($c >> 8) & 255, $c & 255];
    }

    private function decodeAvatar(User $user): GdImage
    {
        $avatar = (string) $user->fresh()->avatar;
        $this->assertStringStartsWith('data:image/jpeg;base64,', $avatar);
        $img = imagecreatefromstring((string) base64_decode(substr($avatar, strlen('data:image/jpeg;base64,'))));
        $this->assertInstanceOf(GdImage::class, $img);

        return $img;
    }

    private function submit(User $user, UploadedFile $file): TestResponse
    {
        return $this->actingAs($user)->from(route('profile.edit'))
            ->post(route('profile.avatar.update'), ['avatar' => $file]);
    }

    public function test_valid_png_becomes_256_square_jpeg_data_uri(): void
    {
        $user = User::factory()->create();
        $src = imagecreatetruecolor(500, 300);
        imagefill($src, 0, 0, imagecolorallocate($src, 10, 120, 200));

        $this->submit($user, $this->upload($this->encode($src), 'a.png'))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $img = $this->decodeAvatar($user);
        $this->assertSame(256, imagesx($img));
        $this->assertSame(256, imagesy($img));
    }

    public function test_transparent_png_is_flattened_on_white(): void
    {
        $user = User::factory()->create();
        $src = imagecreatetruecolor(100, 100);
        imagealphablending($src, false);
        imagesavealpha($src, true);
        imagefill($src, 0, 0, imagecolorallocatealpha($src, 0, 0, 0, 127));

        $this->submit($user, $this->upload($this->encode($src), 'a.png'))->assertSessionHasNoErrors();

        [$r, $g, $b] = $this->pixel($this->decodeAvatar($user), 2, 2);
        $this->assertGreaterThan(245, $r);
        $this->assertGreaterThan(245, $g);
        $this->assertGreaterThan(245, $b);
    }

    public function test_exif_orientation_6_is_applied(): void
    {
        $user = User::factory()->create();
        // Stored landscape 120x80: left half red, right half blue. Orientation 6
        // means "rotate 90 degrees clockwise to display", so upright: red on top, blue below.
        $src = imagecreatetruecolor(120, 80);
        imagefilledrectangle($src, 0, 0, 59, 79, (int) imagecolorallocate($src, 255, 0, 0));
        imagefilledrectangle($src, 60, 0, 119, 79, (int) imagecolorallocate($src, 0, 0, 255));
        $jpeg = $this->encode($src, 'imagejpeg');

        $exif = "Exif\0\0"."MM\0*\0\0\0\x08"."\0\x01"."\x01\x12\0\x03\0\0\0\x01\0\x06\0\0"."\0\0\0\0";
        $app1 = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;
        $jpeg = substr($jpeg, 0, 2).$app1.substr($jpeg, 2);

        $this->submit($user, $this->upload($jpeg, 'photo.jpg'))->assertSessionHasNoErrors();

        $img = $this->decodeAvatar($user);
        [$tr, , $tb] = $this->pixel($img, 128, 20);
        [$br, , $bb] = $this->pixel($img, 128, 236);
        $this->assertTrue($tr > 200 && $tb < 60, 'top should be red');
        $this->assertTrue($bb > 200 && $br < 60, 'bottom should be blue');
    }

    /** @return array<string, array{0: string}> */
    public static function invalidProvider(): array
    {
        return [
            'text file named jpg' => ['text'],
            'gif' => ['gif'],
            'tiny png' => ['tiny'],
            'too large jpeg' => ['large'],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function test_invalid_uploads_are_rejected(string $kind): void
    {
        $user = User::factory()->create(['avatar' => 'data:image/jpeg;base64,AAAA']);

        [$file, $message] = match ($kind) {
            'text' => [$this->upload('đây không phải ảnh', 'a.jpg'), self::MIMES_MESSAGE],
            'gif' => [UploadedFile::fake()->image('a.gif', 200, 200), self::MIMES_MESSAGE],
            'tiny' => [UploadedFile::fake()->image('a.png', 32, 32), 'Ảnh đại diện phải có kích thước tối thiểu 64×64 px.'],
            default => [UploadedFile::fake()->image('a.jpg', 300, 300)->size(3072), 'Ảnh đại diện không được vượt quá 2 MB.'],
        };

        $this->submit($user, $file)->assertSessionHasErrors(['avatar' => $message]);
        $this->assertSame('data:image/jpeg;base64,AAAA', $user->fresh()->avatar);
    }

    public function test_avatar_can_be_removed(): void
    {
        $user = User::factory()->create(['avatar' => 'data:image/jpeg;base64,AAAA']);

        $this->actingAs($user)->delete(route('profile.avatar.destroy'))->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->avatar);
    }
}
