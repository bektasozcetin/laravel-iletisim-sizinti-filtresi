<?php

declare(strict_types=1);

namespace Bektasozcetin\IletisimFiltresi;

/**
 * Serbest metinde telefon, e-posta ve sosyal medya hesabı sızıntısını yakalar.
 *
 * Pazar yerlerinde, randevu platformlarında ve ilan sitelerinde taraflar
 * sistemi atlayıp doğrudan iletişime geçmesin diye kullanılır.
 *
 * Desenler bilerek gevşektir: kullanıcılar numarayı "0 5 3 2", "0.532" ya da
 * "beş yüz otuz iki" diye bölerek yazar. Bu yüzden ayıraçlara izin verilir.
 */
final class IletisimFiltresi
{
    /** Türkçe rakam ve sayı sözcükleri; aksanlı ve aksansız yazımı birlikte kabul eder. */
    private const SAYI_SOZCUKLERI = '(?:s[ıi]f[ıi]r|bir|iki|[üu][çc]|d[öo]rt|be[şs]|alt[ıi]|yedi|sekiz|dokuz|on|yirmi|otuz|k[ıi]rk|elli|altm[ıi][şs]|yetmi[şs]|seksen|doksan|y[üu]z)';

    /** @var array<int, Tur> */
    private array $turler;

    /**
     * @param  array<int, Tur>|null  $turler  Hangi türlerin aranacağı (null = varsayılan küme)
     * @param  string  $maske  Yakalanan metnin yerine konacak dize
     */
    public function __construct(
        ?array $turler = null,
        private readonly string $maske = '[iletişim gizli]',
    ) {
        $this->turler = $turler ?? Tur::varsayilan();
    }

    /** Akıcı kurulum: IletisimFiltresi::make()->ile(Tur::Url) */
    public static function make(?array $turler = null, string $maske = '[iletişim gizli]'): self
    {
        return new self($turler, $maske);
    }

    public function ile(Tur ...$turler): self
    {
        return new self(array_values(array_unique([...$this->turler, ...$turler], SORT_REGULAR)), $this->maske);
    }

    public function haric(Tur ...$turler): self
    {
        return new self(
            array_values(array_filter($this->turler, fn (Tur $t) => ! in_array($t, $turler, true))),
            $this->maske,
        );
    }

    public function maskeyle(string $maske): self
    {
        return new self($this->turler, $maske);
    }

    /** Metni tarar ve maskelenmiş hâliyle birlikte bulguları döner. */
    public function tara(?string $metin): Sonuc
    {
        $orijinal = (string) $metin;

        if (trim($orijinal) === '') {
            return new Sonuc($orijinal, $orijinal, []);
        }

        $bulgular = [];

        foreach ($this->turler as $tur) {
            foreach (self::desenler($tur) as $desen) {
                if (preg_match_all($desen, $orijinal, $eslesmeler, PREG_OFFSET_CAPTURE) === false) {
                    continue;
                }

                foreach ($eslesmeler[0] as [$metinParcasi, $konum]) {
                    if (trim($metinParcasi) === '') {
                        continue;
                    }

                    $bulgular[] = new Bulgu($tur, $metinParcasi, $konum);
                }
            }
        }

        $bulgular = self::ickeIceOlanlariEle($bulgular);

        return new Sonuc($orijinal, $this->maskele($orijinal, $bulgular), $bulgular);
    }

    /** Yalnızca temizlenmiş metni döner. */
    public function temizle(?string $metin): string
    {
        return $this->tara($metin)->temiz;
    }

    public function sizintiVarMi(?string $metin): bool
    {
        return $this->tara($metin)->sizintiVarMi();
    }

    /**
     * Bulguları sondan başa doğru maskeler; böylece konumlar kaymaz.
     *
     * @param  array<int, Bulgu>  $bulgular
     */
    private function maskele(string $metin, array $bulgular): string
    {
        $sirali = $bulgular;
        usort($sirali, fn (Bulgu $a, Bulgu $b) => $b->konum <=> $a->konum);

        foreach ($sirali as $bulgu) {
            $metin = substr_replace($metin, $this->maske, $bulgu->konum, strlen($bulgu->metin));
        }

        return $metin;
    }

    /**
     * Aynı bölgeyi birden fazla desen yakalayabilir (örneğin hem URL hem sosyal).
     * Başka bir bulgunun içinde kalanları atar, kalanları konuma göre sıralar.
     *
     * @param  array<int, Bulgu>  $bulgular
     * @return array<int, Bulgu>
     */
    private static function ickeIceOlanlariEle(array $bulgular): array
    {
        // Önce uzun eşleşmeler kalsın diye uzunluğa göre azalan sırala
        usort($bulgular, fn (Bulgu $a, Bulgu $b) => strlen($b->metin) <=> strlen($a->metin));

        $tutulan = [];

        foreach ($bulgular as $bulgu) {
            $bas = $bulgu->konum;
            $son = $bas + strlen($bulgu->metin);

            foreach ($tutulan as $var) {
                $varBas = $var->konum;
                $varSon = $varBas + strlen($var->metin);

                if ($bas < $varSon && $son > $varBas) {
                    continue 2; // çakışıyor, uzun olan zaten tutuldu
                }
            }

            $tutulan[] = $bulgu;
        }

        usort($tutulan, fn (Bulgu $a, Bulgu $b) => $a->konum <=> $b->konum);

        return $tutulan;
    }

    /**
     * @return array<int, string>
     */
    private static function desenler(Tur $tur): array
    {
        // Rakamların arasına serpiştirilebilen ayıraçlar
        $ayirac = '[\s.\-_()\/\\\\|•·]*';

        return match ($tur) {
            Tur::Telefon => [
                // 5xx xxx xx xx — araya ayıraç girse de yakalanır; başında +90 / 0 olabilir
                '/(?<!\d)(?:\+?9'.$ayirac.'0'.$ayirac.')?0?'.$ayirac.'5(?:'.$ayirac.'\d){9}(?!\d)/u',
                // 0 ile başlayan 11 haneli sabit hat
                '/(?<!\d)0(?:'.$ayirac.'\d){10}(?!\d)/u',
            ],
            Tur::Eposta => [
                '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu',
                // "ali [at] ornek nokta com", "ali (at) ornek dot com"
                '/[A-Z0-9._%+\-]+\s*[\[(]?\s*(?:at|et)\s*[\])]?\s*[A-Z0-9.\-]+\s*[\[(]?\s*(?:dot|nokta)\s*[\])]?\s*[A-Z]{2,}/iu',
            ],
            Tur::Sosyal => [
                '/(?:instagram|facebook|twitter|x|linkedin|tiktok)\.com\/[A-Za-z0-9._\-\/]+/iu',
                // Platform adının ardından \b şart ("Instagram" kendini
                // "insta"+"gram" diye yakalamasın) ve ayıraç zorunlu — yoksa
                // "Instagram hesabımız var" cümlesi de eşleşirdi.
                // Ayıraçsız "instagram @ad" yazımını aşağıdaki @handle deseni yakalar.
                '/\b(?:ig|insta|instagram|fb|facebook|twitter|tiktok|linkedin)\b\s*[:@]\s*@?[A-Za-z0-9._]{2,}/iu',
                '/(?<![A-Za-z0-9._])@[A-Za-z0-9._]{3,30}\b/u',
            ],
            Tur::Mesajlasma => [
                '/(?:wa\.me|api\.whatsapp\.com|chat\.whatsapp\.com)\/[A-Za-z0-9._\-\/?=]+/iu',
                '/t\.me\/[A-Za-z0-9_]+/iu',
                '/\b(?:whatsapp|whats\s?app|wp|telegram|signal|viber|skype)\b/iu',
            ],
            Tur::Url => [
                '/\b(?:https?:\/\/|www\.)[A-Za-z0-9.\-]+\.[A-Za-z]{2,}(?:\/[^\s]*)?/iu',
            ],
            Tur::YaziylaNumara => [
                // Art arda en az altı Türkçe rakam/sayı sözcüğü.
                // Kullanıcılar Türkçe karakter kullanmadan da yazar (bes, uc, dort).
                '/\b(?:'.self::SAYI_SOZCUKLERI.'\s+){5,}'.self::SAYI_SOZCUKLERI.'\b/iu',
            ],
        };
    }
}
