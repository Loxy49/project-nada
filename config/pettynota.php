<?php

return [

    /*
    | Berapa hari setelah dibuat sebuah nota masih boleh diedit oleh karyawan pembuatnya.
    | Nota di periode yang sudah dikunci/diarsipkan accounting tidak bisa diedit, berapa pun harinya.
    | Admin tidak dibatasi hari (hanya oleh kunci periode). Ubah lewat .env: NOTA_EDIT_KARYAWAN_HARI=7
    */
    'edit_karyawan_hari' => (int) env('NOTA_EDIT_KARYAWAN_HARI', 7),

];
