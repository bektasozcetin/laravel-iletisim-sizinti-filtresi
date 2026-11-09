<?php

use Bektasozcetin\IletisimFiltresi\Tur;

return [

    /*
    |--------------------------------------------------------------------------
    | Aranacak türler
    |--------------------------------------------------------------------------
    |
    | Tur::varsayilan() telefon, e-posta, sosyal medya ve mesajlaşma
    | bağlantılarını kapsar. Tur::Url ve Tur::YaziylaNumara yanlış pozitife
    | daha yatkın olduğu için dışarıdadır; ihtiyaç varsa buraya ekle.
    |
    */

    'turler' => Tur::varsayilan(),

    /*
    |--------------------------------------------------------------------------
    | Maske
    |--------------------------------------------------------------------------
    |
    | Yakalanan metnin yerine konacak dize.
    |
    */

    'maske' => '[iletişim gizli]',

];
