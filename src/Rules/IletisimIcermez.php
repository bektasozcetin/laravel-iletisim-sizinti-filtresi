<?php

declare(strict_types=1);

namespace Bektasozcetin\IletisimFiltresi\Rules;

use Bektasozcetin\IletisimFiltresi\IletisimFiltresi;
use Bektasozcetin\IletisimFiltresi\Tur;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Alanın telefon, e-posta ya da sosyal medya hesabı içermemesini şart koşar.
 *
 * Hata mesajı neyin yakalandığını söyler: "Açıklama telefon numarası içeremez."
 */
final class IletisimIcermez implements ValidationRule
{
    /** @var array<int, Tur>|null */
    private ?array $turler;

    public function __construct(Tur ...$turler)
    {
        $this->turler = $turler === [] ? null : $turler;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $sonuc = (new IletisimFiltresi($this->turler))->tara($value);

        if ($sonuc->sizintiVarMi()) {
            $fail('iletisim-filtresi::validation.iletisim_icermez')->translate([
                'sebepler' => implode(', ', $sonuc->sebepler()),
            ]);
        }
    }
}
