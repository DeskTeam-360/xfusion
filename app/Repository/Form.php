<?php

namespace App\Repository;

interface  Form {

    public static function formField($params = null): array;

    public static function formRules(): array;

    public static function formMessages(): array;
}
