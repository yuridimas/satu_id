<?php

arch('actions do not use debugging helpers')
    ->expect('App\Actions')
    ->not->toUse(['dd', 'dump', 'var_dump', 'die', 'exit']);

arch('models extend the eloquent base model')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('policies share the Policy suffix')
    ->expect('App\Policies')
    ->toHaveSuffix('Policy');
