<?php

return [
    'accepted' => ':attribute kabul edilmeli.',
    'between' => [
        'numeric' => ':attribute :min ile :max arasında olmalı.',
        'string' => ':attribute :min ile :max karakter arasında olmalı.',
        'file' => ':attribute :min ile :max KB arasında olmalı.',
    ],
    'boolean' => ':attribute yalnızca evet ya da hayır olabilir.',
    'confirmed' => ':attribute tekrarı eşleşmiyor.',
    'current_password' => 'Mevcut şifre yanlış.',
    'email' => ':attribute geçerli bir e-posta adresi olmalı.',
    'exists' => 'Seçilen :attribute bulunamadı.',
    'file' => ':attribute bir dosya olmalı.',
    'in' => 'Seçilen :attribute geçerli değil.',
    'integer' => ':attribute tam sayı olmalı.',
    'lowercase' => ':attribute küçük harf olmalı.',
    'max' => [
        'numeric' => ':attribute en fazla :max olabilir.',
        'string' => ':attribute en fazla :max karakter olabilir.',
        'file' => ':attribute en fazla :max KB olabilir.',
    ],
    'min' => [
        'numeric' => ':attribute en az :min olmalı.',
        'string' => ':attribute en az :min karakter olmalı.',
        'file' => ':attribute en az :min KB olmalı.',
    ],
    'password' => [
        'letters' => ':attribute en az bir harf içermeli.',
        'mixed' => ':attribute en az bir büyük ve bir küçük harf içermeli.',
        'numbers' => ':attribute en az bir rakam içermeli.',
        'symbols' => ':attribute en az bir sembol içermeli.',
        'uncompromised' => 'Bu şifre daha önce sızan bir listede görünüyor. Başka bir şifre seç.',
    ],
    'regex' => ':attribute biçimi geçersiz.',
    'required' => ':attribute gerekli.',
    'required_if' => ':attribute gerekli.',
    'string' => ':attribute metin olmalı.',
    'unique' => 'Bu :attribute zaten kullanılıyor.',

    'attributes' => [
        'name' => 'ad',
        'email' => 'e-posta',
        'password' => 'şifre',
        'current_password' => 'mevcut şifre',
        'key' => 'anahtar',
        'platform' => 'platform',
        'mafile' => 'maFile',
        'shared_secret' => 'shared_secret',
    ],
];
