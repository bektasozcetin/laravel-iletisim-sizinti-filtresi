<?php

declare(strict_types=1);

use Bektasozcetin\IletisimFiltresi\IletisimFiltresi;
use Bektasozcetin\IletisimFiltresi\Rules\IletisimIcermez;
use Bektasozcetin\IletisimFiltresi\Tur;
use Illuminate\Support\Facades\Validator;

it('iletisim iceren alani reddeder', function (string $metin) {
    expect(Validator::make(['bio' => $metin], ['bio' => [new IletisimIcermez]])->passes())->toBeFalse();
})->with(['ara 0532 123 45 67', 'mail ali@ornek.com', 'ig: drmehmet']);

it('temiz alani gecirir', function () {
    expect(Validator::make(['bio' => '15 yillik deneyimli hekim.'], ['bio' => [new IletisimIcermez]])->passes())->toBeTrue();
});

it('hata mesajinda sebebi soyler', function () {
    app()->setLocale('tr');

    $dogrulayici = Validator::make(['bio' => 'ara 0532 123 45 67'], ['bio' => [new IletisimIcermez]]);

    expect($dogrulayici->passes())->toBeFalse()
        ->and($dogrulayici->errors()->first('bio'))->toContain('telefon numarası');
});

it('kural belirli turlerle sinirlandirilabilir', function () {
    $metin = 'mail ali@ornek.com';

    expect(Validator::make(['bio' => $metin], ['bio' => [new IletisimIcermez(Tur::Telefon)]])->passes())->toBeTrue()
        ->and(Validator::make(['bio' => $metin], ['bio' => [new IletisimIcermez(Tur::Eposta)]])->passes())->toBeFalse();
});

it('dize olmayan degeri gecer', function () {
    expect(Validator::make(['bio' => 42], ['bio' => [new IletisimIcermez]])->passes())->toBeTrue();
});

it('konteynerden yapilandirilmis filtre coculur', function () {
    config()->set('iletisim-filtresi.maske', '***');

    expect(app(IletisimFiltresi::class)->temizle('ara 0532 123 45 67'))->toBe('ara ***');
});
