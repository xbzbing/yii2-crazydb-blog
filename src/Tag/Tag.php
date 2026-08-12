<?php

declare(strict_types=1);

namespace App\Tag;

use Yiisoft\ActiveRecord\ActiveRecord;

final class Tag extends ActiveRecord
{
    public ?int $id = null;
    public string $name = '';
    public int $pid = 0;
    public int $cid = 0;
    public int $create_time = 0;

    public function tableName(): string
    {
        return 'tag';
    }
}
