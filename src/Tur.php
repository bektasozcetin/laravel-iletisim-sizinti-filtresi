<?php

declare(strict_types=1);

namespace Bektasozcetin\IletisimFiltresi;

/** Yakalanabilecek iletişim bilgisi türleri. */
enum Tur: string
{
    case Telefon = 'telefon';
    case Eposta = 'eposta';
    case Sosyal = 'sosyal';
    case Mesajlasma = 'mesajlasma';
    case Url = 'url';
    case YaziylaNumara = 'yaziyla_numara';

    public function etiket(): string
    {
        return match ($this) {
            self::Telefon => 'telefon numarası',
            self::Eposta => 'e-posta adresi',
            self::Sosyal => 'sosyal medya hesabı',
            self::Mesajlasma => 'mesajlaşma bağlantısı',
            self::Url => 'web adresi',
            self::YaziylaNumara => 'yazıyla yazılmış numara',
        };
    }

    /**
     * Varsayılan olarak açık olan türler.
     *
     * YaziylaNumara ve Url yanlış pozitif üretmeye daha yatkın olduğu için
     * dışarıda bırakılır; ihtiyaç duyan açıkça açar.
     *
     * @return array<int, self>
     */
    public static function varsayilan(): array
    {
        return [self::Telefon, self::Eposta, self::Sosyal, self::Mesajlasma];
    }
}
