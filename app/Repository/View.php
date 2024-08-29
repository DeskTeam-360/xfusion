<?php

namespace App\Repository;

use Illuminate\Database\Eloquent\Builder;

interface  View {

    public static function tableSearch($params): Builder;

    public static function tableView(): array;

    public static function tableField(): array;

    public static function tableData($data): array;
}
