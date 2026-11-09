<?php

declare(strict_types=1);

namespace Bektasozcetin\IletisimFiltresi;

/** Tarama sonucu: maskelenmiş metin ve yakalanan bulgular. */
final readonly class Sonuc
{
    /**
     * @param  array<int, Bulgu>  $bulgular
     */
    public function __construct(
        public string $orijinal,
        public string $temiz,
        public array $bulgular,
    ) {}

    public function temizMi(): bool
    {
        return $this->bulgular === [];
    }

    public function sizintiVarMi(): bool
    {
        return $this->bulgular !== [];
    }

    /** @return array<int, Tur> */
    public function turler(): array
    {
        return array_values(array_unique(array_map(fn (Bulgu $b) => $b->tur, $this->bulgular), SORT_REGULAR));
    }

    /**
     * Kullanıcıya gösterilecek sebepler: "telefon numarası", "e-posta adresi" …
     *
     * @return array<int, string>
     */
    public function sebepler(): array
    {
        return array_values(array_unique(array_map(fn (Tur $t) => $t->etiket(), $this->turler())));
    }

    /** @return array{temiz: string, sizinti: bool, sebepler: array<int, string>, bulgular: array<int, array{tur: string, etiket: string, metin: string, konum: int}>} */
    public function toArray(): array
    {
        return [
            'temiz' => $this->temiz,
            'sizinti' => $this->sizintiVarMi(),
            'sebepler' => $this->sebepler(),
            'bulgular' => array_map(fn (Bulgu $b) => $b->toArray(), $this->bulgular),
        ];
    }
}
