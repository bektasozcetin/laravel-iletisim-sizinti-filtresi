# laravel-iletisim-sizinti-filtresi

Serbest metinde **telefon, e-posta, sosyal medya hesabı ve mesajlaşma bağlantısı** sızıntısını yakalar ve maskeler.

Pazar yeri, randevu platformu ve ilan sitelerinde taraflar sistemi atlayıp doğrudan iletişime geçmesin diye kullanılır. Profil biyografisi, ilan açıklaması, hizmet metni, yorum — kullanıcının serbest yazdığı her yerde.

> Detects and masks phone numbers, e-mail addresses, social handles and messaging links in free text. Built for marketplaces and booking platforms where parties should not exchange contact details directly.

[![Tests](https://github.com/bektasozcetin/laravel-iletisim-sizinti-filtresi/actions/workflows/tests.yml/badge.svg)](https://github.com/bektasozcetin/laravel-iletisim-sizinti-filtresi/actions/workflows/tests.yml)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

## Neden

Kullanıcılar numarayı düz yazmaz. Filtreyi aşmak için böler:

```
0532 123 45 67        0 5 3 2 1 2 3 4 5 6 7       0532.123.45.67
ali [at] ornek nokta com                          ig: drmehmet
sıfır beş üç iki bir iki üç dört beş altı yedi
```

Bu paket hepsini yakalar. Desenler ayıraçlara (boşluk, nokta, tire, parantez, slash, dikey çizgi) toleranslıdır.

## Kurulum

```bash
composer require bektasozcetin/laravel-iletisim-sizinti-filtresi
```

İsteğe bağlı:

```bash
php artisan vendor:publish --tag=iletisim-filtresi-config
php artisan vendor:publish --tag=iletisim-filtresi-lang
```

**Gereksinimler:** PHP 8.2+, Laravel 11 veya 12.

## Doğrulama kuralı

Kaydı tamamen reddetmek için:

```php
use Bektasozcetin\IletisimFiltresi\Rules\IletisimIcermez;

$request->validate([
    'biyografi' => ['required', 'string', new IletisimIcermez],
]);

// "Biyografi iletişim bilgisi içeremez (telefon numarası, e-posta adresi)."
```

Belirli türlerle sınırla:

```php
use Bektasozcetin\IletisimFiltresi\Tur;

new IletisimIcermez(Tur::Telefon, Tur::Eposta);
```

## Maskeleme

Reddetmek yerine sessizce temizlemek için:

```php
use Bektasozcetin\IletisimFiltresi\IletisimFiltresi;

$filtre = app(IletisimFiltresi::class);          // config'ten yapılandırılmış
$filtre = IletisimFiltresi::make();              // ya da elle

$filtre->temizle('Beni 0532 123 45 67 arayın');
// "Beni [iletişim gizli] arayın"

$filtre->sizintiVarMi('ali@ornek.com');          // true
```

### Ayrıntılı sonuç

```php
$sonuc = $filtre->tara('Tel 0532 123 45 67, mail ali@ornek.com');

$sonuc->temiz;            // "Tel [iletişim gizli], mail [iletişim gizli]"
$sonuc->sizintiVarMi();   // true
$sonuc->temizMi();        // false
$sonuc->sebepler();       // ["telefon numarası", "e-posta adresi"]
$sonuc->turler();         // [Tur::Telefon, Tur::Eposta]
$sonuc->bulgular;         // Bulgu[] — tur, metin, konum
$sonuc->toArray();        // API yanıtı için
```

Her `Bulgu` bayt konumunu taşır; arayüzde sızıntıyı vurgulamak için kullanabilirsin.

## Türler

| Tür | Yakaladığı | Varsayılan |
|---|---|---|
| `Tur::Telefon` | 0532…, +90…, bölünmüş rakamlar, 11 haneli sabit hat | ✅ |
| `Tur::Eposta` | `ali@ornek.com`, `ali [at] ornek nokta com` | ✅ |
| `Tur::Sosyal` | `@handle`, `ig: ad`, `instagram.com/ad` | ✅ |
| `Tur::Mesajlasma` | `wa.me/…`, `t.me/…`, "WhatsApp", "Telegram" | ✅ |
| `Tur::Url` | `https://…`, `www.…` | ❌ |
| `Tur::YaziylaNumara` | "sıfır beş üç iki bir iki üç…" (aksansız yazım dahil) | ❌ |

`Url` ve `YaziylaNumara` yanlış pozitife daha yatkın olduğu için kapalı gelir.

```php
IletisimFiltresi::make()->ile(Tur::Url, Tur::YaziylaNumara);
IletisimFiltresi::make()->haric(Tur::Mesajlasma);
IletisimFiltresi::make()->maskeyle('***');
```

Filtre değiştirilemez (immutable): her çağrı yeni bir nesne döner.

## Yapılandırma

`config/iletisim-filtresi.php`:

```php
return [
    'turler' => Tur::varsayilan(),   // ya da [Tur::Telefon, Tur::Eposta, Tur::Url]
    'maske' => '[iletişim gizli]',
];
```

## Yanlış pozitifler hakkında

Filtre bilerek temkinlidir — kaçırmaktansa fazladan yakalaması yeğdir. Yine de şunlar **maskelenmez:**

```
"2026 yılında 350 hasta baktık."        → sayı, numara değil
"Instagram hesabımız var."              → ayıraç yok, kullanıcı adı yok
"Seans ücreti 1500 TL, süre 50 dakika." → temiz
```

Sosyal medya deseni platform adından sonra `:` veya `@` ister; aksi hâlde sıradan cümleler de eşleşirdi. Ayıraçsız `instagram @ad` yazımını `@handle` deseni ayrıca yakalar.

> Bu bir moderasyon yardımcısıdır, güvenlik sınırı değil. Yeterince yaratıcı bir kullanıcı her filtreyi aşar; kritik akışlarda insan incelemesiyle birlikte kullan.

## Test

```bash
composer install
composer test
```

## Lisans

MIT. Ayrıntı için [LICENSE](LICENSE).
