<?php

declare(strict_types=1);

use Bektasozcetin\IletisimFiltresi\IletisimFiltresi;
use Bektasozcetin\IletisimFiltresi\Tur;

function filtre(): IletisimFiltresi
{
    return IletisimFiltresi::make();
}

it('telefon numarasini yakalar', function (string $metin) {
    $sonuc = filtre()->tara($metin);

    expect($sonuc->sizintiVarMi())->toBeTrue()
        ->and($sonuc->sebepler())->toContain('telefon numarası')
        ->and($sonuc->temiz)->toContain('[iletişim gizli]');
})->with([
    'Beni 0532 123 45 67 numarasindan arayin',
    'Tel: +90 532 123 45 67',
    '5321234567 numaram',
    'Numaram 0 5 3 2 1 2 3 4 5 6 7',       // bosluklarla bolunmus
    'ara: 0532.123.45.67',
    'ulas: 0532-123-45-67',
]);

it('e-posta adresini yakalar', function (string $metin) {
    expect(filtre()->tara($metin)->sebepler())->toContain('e-posta adresi');
})->with([
    'yazin: ali.veli@ornek.com',
    'ali [at] ornek nokta com',
    'ali (at) ornek dot com adresine',
]);

it('sosyal medya hesabini yakalar', function (string $metin) {
    expect(filtre()->tara($metin)->sebepler())->toContain('sosyal medya hesabı');
})->with([
    'Instagram: @drmehmet',
    'ig: drmehmet',
    'instagram.com/klinik.ankara',
    '@drmehmet hesabi',
]);

it('mesajlasma baglantisini yakalar', function (string $metin) {
    expect(filtre()->tara($metin)->sebepler())->toContain('mesajlaşma bağlantısı');
})->with(['wa.me/905321234567', 'WhatsApp hattimiz', 't.me/drmehmet', 'Telegram uzerinden']);

it('masum metni degistirmez', function (string $metin) {
    $sonuc = filtre()->tara($metin);

    expect($sonuc->temizMi())->toBeTrue()
        ->and($sonuc->temiz)->toBe($metin);
})->with([
    'Cankaya|da 15 yillik deneyimli hekim.',
    '2026 yilinda 350 hasta baktik.',
    'Instagram hesabimiz var, profilden ulasabilirsiniz.',
    'Randevu icin panelden talep olusturun.',
    'Seans ucreti 1500 TL, sure 50 dakika.',
    '',
]);

it('bos ve null girdiyi gecer', function () {
    expect(filtre()->tara(null)->temizMi())->toBeTrue()
        ->and(filtre()->tara('   ')->temizMi())->toBeTrue();
});

it('birden fazla sizintiyi ayri ayri raporlar', function () {
    $sonuc = filtre()->tara('Tel 0532 123 45 67, mail ali@ornek.com, ig: drmehmet');

    expect($sonuc->bulgular)->toHaveCount(3)
        ->and($sonuc->sebepler())->toHaveCount(3)
        ->and(substr_count($sonuc->temiz, '[iletişim gizli]'))->toBe(3);
});

it('ayni bolgeyi iki desen yakalarsa tek kez maskeler', function () {
    $sonuc = IletisimFiltresi::make()->ile(Tur::Url)->tara('https://instagram.com/drmehmet');

    expect($sonuc->bulgular)->toHaveCount(1)
        ->and(substr_count($sonuc->temiz, '[iletişim gizli]'))->toBe(1);
});

it('maske degistirilebilir', function () {
    expect(IletisimFiltresi::make()->maskeyle('***')->temizle('ara 0532 123 45 67'))->toBe('ara ***');
});

it('tur eklenip cikarilabilir', function () {
    $metin = 'WhatsApp hattimiz acik';

    expect(filtre()->tara($metin)->sizintiVarMi())->toBeTrue()
        ->and(filtre()->haric(Tur::Mesajlasma)->tara($metin)->temizMi())->toBeTrue();
});

it('yaziyla yazilmis numarayi istege bagli yakalar', function () {
    $metin = 'numaram sifir bes uc iki bir iki uc dort bes alti yedi';

    expect(filtre()->tara($metin)->temizMi())->toBeTrue()  // varsayilan kapali
        ->and(filtre()->ile(Tur::YaziylaNumara)->tara($metin)->sizintiVarMi())->toBeTrue();
});

it('turkce karaktersiz yazimi da yakalar', function () {
    $f = filtre()->ile(Tur::YaziylaNumara);

    expect($f->tara('sıfır beş üç iki bir iki üç dört beş altı yedi')->sizintiVarMi())->toBeTrue()
        ->and($f->tara('sifir bes uc iki bir iki uc dort bes alti yedi')->sizintiVarMi())->toBeTrue();
});

it('bulgu konumu ve metni dogru verir', function () {
    $sonuc = filtre()->tara('ara 0532 123 45 67 simdi');
    $bulgu = $sonuc->bulgular[0];

    expect($bulgu->tur)->toBe(Tur::Telefon)
        ->and($bulgu->metin)->toBe('0532 123 45 67')
        ->and($bulgu->konum)->toBe(4)
        ->and($bulgu->toArray())->toHaveKeys(['tur', 'etiket', 'metin', 'konum']);
});

it('sonucu diziye cevirir', function () {
    expect(filtre()->tara('ara 0532 123 45 67')->toArray())
        ->toHaveKeys(['temiz', 'sizinti', 'sebepler', 'bulgular']);
});
