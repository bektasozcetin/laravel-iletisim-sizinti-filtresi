<?php

declare(strict_types=1);

namespace Bektasozcetin\IletisimFiltresi;

/** Metinde yakalanan tek bir iletişim bilgisi. */
final readonly class Bulgu
{
    /**
     * @param  int  $konum  Bayt cinsinden başlangıç konumu (preg_match_all OFFSET_CAPTURE)
     */
    public function __construct(
        public Tur $tur,
        public string $metin,
        public int $konum,
    ) {}

    /** @return array{tur: string, etiket: string, metin: string, konum: int} */
    public function toArray(): array
    {
        return [
            'tur' => $this->tur->value,
            'etiket' => $this->tur->etiket(),
            'metin' => $this->metin,
            'konum' => $this->konum,
        ];
    }
}
