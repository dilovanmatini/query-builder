<?php

use Database\QueryBuilder\QB;

$users = QB::select([
    QB::alias('u', [
        'id',
        'name',
        'email',
        'password',
        'created_at',
        'updated_at'
    ]),
    'COUNT(p.*) AS count',
    QB::count('p.id')->as('count'),
    'd.name AS department_name',
    QB::if(
        QB::where('s.id', QB::isNotNull())->and('b.id', QB::isNull()),
        QB::where('s.id'),
        0
    )->as('schedule')
])
    ->from('users')->as('u')
    ->leftJoin('posts')->as('p')->on('u.id', 'p.user_id')
    ->innerJoin('departments', 'd')->on('u.department_id = d.id')->and('d.status', QB::param(1))
    ->leftJoin('schedule')->as('s')->on('u.id', 's.user_id')
    ->where('u.status', 1)->and('u.id', 1)->and('u.name', QB::notEmpty())->and('u.email', '')->or(
        QB::where('u.id', 2)
            ->and('u.name = ""')
            ->and('u.email', '')
            ->or(
                QB::where('u.id', 3)
                    ->and('u.name', '')
                    ->and('u.email', '')
                    ->and(
                        QB::if(
                            QB::where('u.id', 4),
                            'u.name',
                            'u.email'
                        )
                    )
            )
    )
    ->groupBy('u.id', 'u.email')
    ->orderBy('u.id DESC', 'u.email')
    ->having('count', '>', 0)->and('count', '<', 10)
    ->limit(10)
    ->offset(0)
    ->fetch();