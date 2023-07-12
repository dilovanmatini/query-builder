# Query Builder
This library offers a seamless and natural way to construct database queries in PHP that closely resembles writing queries in the SQL native language.

## Requirements
- PHP >= 8.1
- PDO PHP Extension
- HTTP server with PHP support (e.g. Apache, Nginx, Caddy)

## Installation
```bash
composer require dilovanmatini/query-builder
```

## Usage

Using PDO Instance:

```php
<?php

use Database\QueryBuilder\QB;

require_once __DIR__ . '/vendor/autoload.php';

QB::config([
    'connection' => $conn // PDO instance
]);

$user = QB::select('id, name, email')->from('users')->where('id', 1)->and('status', 1)->fetch();

echo $user->name;
```

In Laravel:

```php
<?php

use Database\QueryBuilder\QB;

$user = QB::select('id, name, email')->from(User::class)->where('id', 1)->and('status', 1)->fetch();

echo $user->name;
```

> In Laravel, the connection detects automatically.

Using Database Credentials:

```php
<?php

use Database\QueryBuilder\QB;

require_once __DIR__ . '/vendor/autoload.php';

QB::config([
    'host' => 'localhost',
    'database' => 'test',
    'username' => 'root',
    'password' => '',
]);

$user = QB::select('id, name, email')->from('users')->where('id', 1)->and('status', 1)->fetch();

echo $user->name;
```

<br>

## Configuration

|Option| DataType  | Default Value| Explanation                                                                                                   |
|--|--|--|---------------------------------------------------------------------------------------------------------------|
|`connection`|`PDO`|`null`| PDO Instance                                                                                                  |
|`audit_callback`|`function`|`null`| The given callback function will be called after executing INSERT, UPDATE, and DELETE queries                 |
|`soft_delete`|`bool`|`false`| It provides soft delete functionality                                                                         |
|`soft_delete_column`|`string`|`deleted_at`| It accepts a column name for soft delete functionality                                                        |
|`timestamp`|`string`|`now()`| It accepts a timestamp value for soft delete functionality                                                    |
|`model_class`|`string`|`null`| It accepts a Model class name especially for projects using MVC pattern. In Laravel, you don't need to set it |
|`host`|`string`|`127.0.0.1`| Database host name or IP address                                                                              |
|`port`|`int`|`3306`| Database port number                                                                                          |
|`database`|`string`|`""`| Database name                                                                                                 |
|`username`|`string`|`root`| Database username                                                                                             |
|`password`|`string`|`""`| Database password                                                                                             |
|`charset`|`string`|`utf8mb4`| Database charset                                                                                              |

>If you set a valid `connection` you don't need to use `host`, `port`, `database`, `username`, `password`, and `charset` options.

<br>

## Documentation

- [Select](#select)
  - [Select](#select)
  - [From](#from)
  - [Joins](#joins)
  - [Where](#where)
  - [Group By](#group-by)
  - [Order By](#order-by)
  - [Having](#having)
  - [Limit](#limit)
  - [Offset](#offset)
  - [Fetch, FetchAll, Statement](#fetch)
- [Insert](#insert)
  - [Columns](#columns)
  - [Values](#values)
- [Update](#update)
    - [Set](#set)
    - [Where](#where)
- [Delete](#delete)
    - [Where](#where)

### Select
The `select()` method is used to add `SELECT` clause to the query to specify which columns you would like to retrieve from the database. The `select()` method accepts list of arguments as columns, so you can pass the columns as a comma-separated list or as an array.

To retrieve all columns from a table, you may use the `select()` method without passing any arguments.
```php
$user = QB::select()->from('users');
```
```SQL
SELECT * FROM users
```

> **Note:** The second code is SQL code that will be generated by the first code.

<br>

To retrieve a single column, pass the name of the column as the first argument to the `select()` method.

```php
$name = QB::select('name')->from('users');
```
```SQL
SELECT name FROM users
```

<br>

To retrieve multiple columns, pass the names of the columns as an array to the `select()` method.

```php
$user = QB::select(['id', 'name', 'email'])->from('users');
```
```SQL
SELECT id, name, email FROM users
```

<br>

Or you can pass the columns as a comma-separated list to the `select()` method.

```php
$user = QB::select('id, name, email')->from('users');
```
```SQL
SELECT id, name, email FROM users
```

<br>

Multiple usage of `select()` in one query:

```php
$user = QB::select([
        'u.id'
        QB::alias('u', [
            'name',
            'email',
        ]),
        QB::if('u.status = 1', 'active', 'inactive')->as('status'),
        QB::count('p.*')->as('count'),
    ], 'u.join_date')
    ->from('users AS u')
    ->leftJoin('posts')->as('p')->on('p.user_id', 'u.id');
```
```SQL
SELECT
    u.id,
    u.name,
    u.email,
    IF(u.status = 1, 'active', 'inactive') AS status,
    COUNT(p.*) AS count,
    u.join_date
FROM users AS u
LEFT JOIN posts AS p ON p.user_id = u.id
```

<br>

`select()` has some helpers to make it easier to write queries:

#### Alias

The `alias()` method is used to add alias for the columns. The `alias()` method accepts two arguments. The first argument is alias name and the second argument is the list of columns.

```php
$user = QB::select([
        QB::alias('u', [
            'name',
            'email',
        ]),
    ])
    ->from('users AS u');
```
```SQL
SELECT u.name, u.email FROM users AS u
```

<br>

#### Count

The `count()` method is used to add `COUNT()` function to the query. The `count()` method accepts two arguments. The first argument is the column name and the second argument is optional and is used to specify the alias for the column.

> You can also use `as()` method instead of passing the alias as the second argument.

```php
$user = QB::select([
        QB::count('p.*')->as('count'),
    ])
    ->from('users AS u')
    ->leftJoin('posts')->as('p')->on('p.user_id', 'u.id');
```
```SQL
SELECT COUNT(p.*) AS count FROM users AS u LEFT JOIN posts AS p ON p.user_id = u.id
```

The same way for the `sum` `min` `max` `avg` methods.

<br>

### From

The `from()` method is used to add `FROM` clause to the query to specify the table from which you would like to retrieve data. The `from()` method accepts a string variable or a model class name as its first argument. The second argument is optional and is used to specify the alias for the table.

```php
$user = QB::select()->from('users');
```
```SQL
SELECT * FROM users
```

<br>

Or using **Model** class name `For developers who use MVC framework like Laravel`

```php
$user = QB::select()->from(User::class);
```
```SQL
SELECT * FROM users
```

<br>

You can also specify the alias for the table as the second argument to the `from()` method.
```php
$user = QB::select()->from('users', 'u');
```
```SQL
SELECT * FROM users AS u
```

<br>

Or using the `as()` method
```php
$user = QB::select()->from('users')->as('u');
```
```SQL
SELECT * FROM users AS u
```

<br>

### Joins

The joins methods are used to join tables in a query.

The `leftJoin()` `rightJoin()` `crossJoin()` `innerJoin` `fullJoin()` methods accept the table name as the first argument and the alias for the table as the second argument. You can use `as()` method instead of passing the alias as the second argument.

> If you don't provide alias for the tables, the table name will be used as the alias when you have more than one table in the query.

```php
$user = QB::select()
        ->from('users')->as('u')
        ->leftJoin('posts')->as('p');
```
```SQL
SELECT u.* FROM users AS u LEFT JOIN posts AS p
```

<br>

You can also use the `on()` method to specify the join condition.
```php
$user = QB::select()
        ->from('users')->as('u')
        ->leftJoin('posts')->as('p')->on('u.id', 'p.user_id');
```
```SQL
SELECT u.* FROM users AS u LEFT JOIN posts AS p ON u.id = p.user_id
```

<br>

Using more than one joins in a query.
```php
$user = QB::select()
        ->from('users')->as('u')
        ->leftJoin('posts')->as('p')->on('u.id', 'p.user_id')
        ->leftJoin('comments')->as('c')->on('c.post_id', 'p.id');
```
```SQL
SELECT u.*
FROM users AS u
LEFT JOIN posts AS p ON u.id = p.user_id
LEFT JOIN comments AS c ON c.post_id = p.id
```

<br>

### Where

The `where()` method is used to add a `WHERE` clause to the query. The `where()` method accepts three arguments. The first argument is required and others are optional.

> If you pass only `one` argument to the `where()` method, it will be considered as the full condition consists of column name, operator, and value.

> If you pass `two` arguments to the `where()` method, the first argument will be considered as the column name and the second argument will be considered as the value. The `=` operator will be used as the default operator.

> If you pass `three` arguments to the `where()` method, the first argument will be considered as the column name, the second argument will be considered as the operator, and the third argument will be considered as the value.

<br>

Example using only `one` argument:
```php
$user = QB::select()->from('users')->where('id = 1');
```
```SQL
SELECT * FROM users WHERE id = 1
```

<br>

Example using `two` arguments:
```php
$user = QB::select()->from('users')->where('id', 1);
```
```SQL
SELECT * FROM users WHERE id = 1
```

<br>

Example using `three` arguments:
```php
$user = QB::select()->from('users')->where('status', '!=', 0);
```
```SQL
SELECT * FROM users WHERE status != 0
```

> **Note:** if you want to pass the `RAW` value as the second and third arguments, you should use the `QB::raw()` method.

<br>
You can also use the `and()` and `or()` methods to add more conditions to the `WHERE` clause.

Using the `and()` method:
```php
$user = QB::select()
        ->from('users')
        ->where('id', 1)
        ->and('status', 1);
```
```SQL
SELECT * FROM users WHERE id = 1 AND status = 1
```

<br>

Using the `or()` method:
```php
$user = QB::select()
        ->from('users')
        ->where('skill', 'php')
        ->or('skill', 'javascript');
```
```SQL
SELECT * FROM users WHERE skill = 'php' OR skill = 'javascript'
```

<br>

Using the `and()` and `or()` methods together:
```php
$users = QB::select()
        ->from('users')
        ->where('hobie', 'tines')
        ->or('hobie', 'coding')
        ->and('skill', 'php');
```
```SQL
SELECT * FROM users WHERE (hobie = 'tines' OR hobie = 'coding') AND skill = 'php'
```

<br>

You can use group conditions using the `where()` `and()` `or()` `on()` `having` methods. Especially when you use the `and()` and `or()` methods together.

```php
$user = QB::select()
        ->from('users')
        ->where('id', 1)
        ->and('status', 1)
        ->and(
            QB::where('skill', 'php')->or('skill', 'javascript')
        );
```
```SQL
SELECT * FROM users WHERE id = 1 AND status = 1 AND (skill = 'php' OR skill = 'javascript')
```

<br>

#### Where helpers

The `where()` method also accepts the group of method helpers to provide more flexibility to the query.

List of where helpers:
- `QB::equal( $value )`
- `QB::notEqual( $value )`
- `QB::lessThan( $value )`
- `QB::lessThanOrEqual( $value )`
- `QB::greaterThan( $value )`
- `QB::greaterThanOrEqual( $value )`
- `QB::like( $value )`
- `QB::notLike( $value )`
- `QB::between( $value1, $value2 )`
- `QB::notBetween( $value1, $value2 )`
- `QB::in( $values )`
- `QB::notIn( $values )`
- `QB::isNull()`
- `QB::isNotNull()`
- `QB::isEmpty()`
- `QB::isNotEmpty()`

> All the above helpers can be used as the second or third argument of the `where()` `and()` `or()` `on()` `having` methods.

Some examples:

```php
$user = QB::select()
        ->from('users')
        ->where('id', QB::equal(1))
        ->and('status', QB::notEqual(0))
        ->and('skills', QB::in(['php', 'javascript']));
```
```SQL
SELECT * FROM users WHERE id = 1 AND status != 0 AND skills IN ('php', 'javascript')
```

<br>

```php
$users = QB::select()
        ->from('users')
        ->where('id', QB::between(1, 10))
        ->and('status', QB::notBetween(0, 5));
```
```SQL
SELECT * FROM users WHERE id BETWEEN 1 AND 10 AND status NOT BETWEEN 0 AND 5
```

<br>

```php
$users = QB::select()
        ->from('users')
        ->where('name', QB::like('%john%'))
        ->and('skill', QB::notLike('%script'))
        ->and('birthday', QB::isNotNull())
        ->and('hobie', QB::isNotEmpty());
```
```SQL
SELECT *
FROM users
WHERE
    name LIKE '%john%' AND
    skill NOT LIKE '%script' AND
    birthday IS NOT NULL AND
    hobie IS NOT NULL
```

> **Note:** `like()` and `notLike()` don't make the value as placeholder, so you can pass the value directly. But if you want to make the value as placeholder, you can use the `QB::param()` method for the value.

<br>

### Group by

The `groupBy()` method is used to add a `GROUP BY` clause to the query. The `groupBy()` method accepts list of columns as arguments.

```php
$users = QB::select()
        ->from('users')
        ->groupBy('skill');
```
```SQL
SELECT * FROM users GROUP BY skill
```

<br>

```php
$users = QB::select()
        ->from('users')
        ->groupBy('skill', 'hobie');
```
```SQL
SELECT * FROM users GROUP BY skill, hobie
```

<br>

### Order by

The `orderBy()` method is used to add a `ORDER BY` clause to the query. The `orderBy()` method accepts multiple arguments.

> If you don't pass `ASC` or `DESC`, the `ASC` will be used as the default order.

```php
$users = QB::select()
        ->from('users')
        ->orderBy('name');
```
```SQL
SELECT * FROM users ORDER BY name ASC
```

<br>

```php
$users = QB::select()
        ->from('users')
        ->orderBy('name ASC', 'join_date DESC');
```
```SQL
SELECT * FROM users ORDER BY name ASC, join_date DESC
```

<br>

### Having

The `having()` method is used to add a `HAVING` clause to the query. The `having()` method is similar to the `where()` method.

```php
$users = QB::select()
        ->from('users')
        ->groupBy('skill')
        ->having('COUNT(id)', '>', 10);
```
```SQL
SELECT * FROM users GROUP BY skill HAVING COUNT(id) > 10
```

<br>

```php
$users = QB::select()
        ->from('users')
        ->groupBy('skill')
        ->having('COUNT(id)', '>', 10)
        ->and('COUNT(id)', '<', 20);
```
```SQL
SELECT * FROM users GROUP BY skill HAVING COUNT(id) > 10 AND COUNT(id) < 20
```

<br>

### Limit

The `limit()` method is used to add a `LIMIT` clause to the query. The `limit()` method accepts one argument.

```php
$users = QB::select()
        ->from('users')
        ->limit(10);
```
```SQL
SELECT * FROM users LIMIT 10
```

<br>

### Offset

The `offset()` method is used to add a `OFFSET` clause to the query. The `offset()` method accepts one argument.

> The `offset()` method must be used with the `limit()` method.

```php
$users = QB::select()
        ->from('users')
        ->limit(10)
        ->offset(100);
```
```SQL
SELECT * FROM users LIMIT 10, 100
```

<br>

### Fetch, FetchAll, and Statement

The `fetch()` method is used to fetch data from the database. The `fetch()` method accepts one argument as the fetch mode.

> The default fetch mode is `PDO::FETCH_OBJ`. You can pass any fetch mode from the `PDO` class.

To fetch data as an associative array:

> You can use `fetch` when you want to fetch only one row.

```php
$user = QB::select()
        ->from('users')
        ->where('id', 1)
        ->fetch(\PDO::FETCH_ASSOC);

echo $user['name']; // John Doe
```

<br>

The `fetchAll()` method is used to fetch all data from the database. The `fetchAll()` method accepts one argument as the fetch mode.

> You can use `fetchAll` when you want to fetch all rows.

```php
$users = QB::select()
        ->from('users')
        ->fetchAll();

foreach ($users as $user) {
    $user->name; // John Doe
}
```

<br>

The `statement()` method is used to get the `PDOStatement` object.

> You can use `statement` when you want to use the `PDOStatement` methods.

```php
$stmt = QB::select()
        ->from('users')
        ->statement();

while ($user = $stmt->fetch()) {
    echo $user->name; // John Doe
}
```

<br>

#### Raw

The `raw()` method is used to return the raw query string. The `raw()` method accepts one argument to indicate whether you want the query as a string or an stdClass object including parameters used as placeholders.

```php
$query = QB::select()
        ->from('users')
        ->where('id', 1)
        ->raw();
```
```SQL
SELECT * FROM users WHERE id = 1
```

<br>

### Some other helpers

#### Raw

The `raw()` method is used to add a raw string to the query. The `raw()` method accepts one argument.

```php
$users = QB::select()
        ->from('users')
        ->where('id', QB::raw('COUNT(id)'))
```
```SQL
SELECT * FROM users WHERE id = COUNT(id)
```

<br>

#### Param

The `param()` method is used to add the value as placeholder to the query. The `param()` method accepts two arguments. The first one is required as the value and the second one is optional as the name of the placeholder.

```php

$users = QB::select()
        ->from('users')
        ->leftJoin('skills' 's')->on('s.user_id', 'u.id')->and('s.name', QB::param('php', 'skill'))
        ->where('id', 1);
```
```SQL
SELECT * FROM users LEFT JOIN skills s ON s.user_id = u.id AND s.name = :skill WHERE id = 1
```

> You don't need to use the `param()` method for the `where()` clause. The `where()` automatically adds the value as placeholder.

<br>

#### Now

The `now()` method is used to add the current date and time to the query.

```php
$users = QB::select()
        ->from('users')
        ->where('created_at', QB::now());
```
```SQL
SELECT * FROM users WHERE created_at = NOW()
```

<br>

### Some other examples from simple to complex

```php

$users = QB::select()
        ->from('users')
        ->where('id', 1)
        ->and('status', 1)
        ->and(
            QB::where('skill', 'php')->or('skill', 'javascript')
        )
        ->groupBy('skill')
        ->having('COUNT(id)', '>', 10)
```
```SQL
SELECT *
FROM users
WHERE
    id = 1 AND
    status = 1 AND
    (skill = 'php' OR skill = 'javascript')
GROUP BY skill
HAVING COUNT(id) > 10
```

<br>

```php

$users = QB::select()
        ->from('users')
        ->where('id', 1)
        ->and('status', 1)
        ->and(
            QB::where('skill', 'php')->or('skill', 'javascript')
        )
        ->groupBy('skill')
        ->orderBy('name ASC', 'join_date DESC')
        ->having('COUNT(id)', '>', 10)
        ->limit(10)
        ->offset(100);
```
```SQL
SELECT *
FROM users
WHERE
    id = 1 AND
    status = 1 AND
    (skill = 'php' OR skill = 'javascript')
GROUP BY skill
HAVING COUNT(id) > 10
ORDER BY name ASC, join_date DESC
LIMIT 10, 100
```

<br>

```php

$users = QB::select(
        'u.id, u.name, u.email, s.name as skill',
        QB::if('u.status = 1', 'active', 'inactive')->as('status'),
        QB::select('COUNT(id)')->from('skills')->where('user_id', QB::raw('u.id'))->as('skill_count')
    )
    ->from('users')->as('u')
    ->leftJoin('posts', 'p')->on('p.user_id', 'u.id')
    ->leftJoin('comments')->as('c')->on('c.post_id', 'p.id')
    ->where('u.status', 1)
    ->and(
        QB::where('u.skill', 'php')->or('u.skill', 'javascript')
    )
    ->and('u.join_date', QB::greaterThan('2019-01-01'))
    ->and('u.role',
        QB::if(
            QB::where('u.role', QB::in('admin', 'super_admin')),
            'admin',
            'user'
        )
    )
    ->groupBy('u.skill')
    ->having('COUNT(p.id)', '>', 10)
    ->orderBy('u.name ASC', 'u.join_date DESC')
    ->limit(10)
    ->having('COUNT(c.id)', '<', 100)
    ->offset(100);
```
```SQL
SELECT
    u.id, u.name, u.email, s.name as skill,
    IF(u.status = 1, 'active', 'inactive') as status,
    (SELECT COUNT(id) FROM skills WHERE user_id = u.id) as skill_count
FROM users u
LEFT JOIN posts p ON p.user_id = u.id
LEFT JOIN comments c ON c.post_id = p.id
WHERE
    u.status = 1 AND
    (u.skill = 'php' OR u.skill = 'javascript') AND
    u.join_date > '2019-01-01' AND
    u.role = IF(u.role IN ('admin', 'super_admin'), 'admin', 'user')
GROUP BY u.skill
HAVING COUNT(p.id) > 10 AND COUNT(c.id) < 100
ORDER BY u.name ASC, u.join_date DESC
LIMIT 10, 100
```

<br>

### Insert

The `QB::insert()` method is used to create an `INSERT` query. The `insert()` method accepts one argument as the table name.

> You can also use alternative methods like `insertInto()`. They are the same.

```php
QB::insert('users')->values([
    'name' => 'John Doe'
]);
```
```SQL
INSERT INTO users (name) VALUES ('John Doe')
```

> You can use **Model** classes instead of table names.

<br>

#### Columns

The `columns()` method is used to add `COLUMNS` to the `INSERT` query. The `columns()` method is optional and accepts the below arguments:

- A string as RAW SQL.
- An array as column names.

> If you pass a string to the `columns()`, the `values()` method must be string too.

> If you pass an array to the `values()` method, you don't need to use the `columns()` method.

```php
QB::insert('users')->columns('name')->values('John Doe');
```
```SQL
INSERT INTO users (name) VALUES ('John Doe')
```

<br>

#### Values

The `values()` method is used to add `VALUES` to the `INSERT` query. The `values()` method accepts the below arguments:

- A string as RAW SQL.
- An array as column names and values.

> When you pass an array to the `values()` method, the keys of the array are the column names and the values of the array are the values of the columns.

```php
QB::insert('users')->values([
    'name' => 'John Doe'
]);
```
```SQL
INSERT INTO users (name) VALUES ('John Doe')
```

> The values of the array will be placeholders automatically.

<br>

To execute the query, you can use the `run()` or `execute()` methods.

```php
QB::insert('users')->values([
    'name' => 'John Doe'
])->run();
```

<br>

### Update

The `QB::update()` method is used to create an `UPDATE` query. The `update()` method accepts two arguments as the table name and the alias of the table.

> You can also use `as()` method to set the alias of the table.

```php
QB::update('users')->set([
    'name' => 'John Doe'
])->where('id', 1);
```
```SQL
UPDATE users SET name = 'John Doe' WHERE id = 1
```

> You can use **Model** classes instead of table names.

<br>

#### Set

The `set()` method is used to add `SET` to the `UPDATE` query. The `set()` method accepts the below arguments:

- A string as RAW SQL.
- An array as column names and values.

> When you pass an array to the `set()` method, the keys of the array are the column names and the values of the array are the values of the columns.

```php
QB::update('users')->set([
    'name' => 'John Doe'
])->where('id', 1);
```
```SQL
UPDATE users SET name = 'John Doe' WHERE id = 1
```

> The values of the array will be placeholders automatically.

<br>

> To use `where()` method with `update()` method, please see the [Where](#where) section.

> **Note:** you <span style="color: red;">cannot</span> use `update()` method without using `where()` method.

<br>

To execute the query, you can use the `run()` or `execute()` methods.

```php
QB::update('users')->set([
    'name' => 'John Doe'
])->where('id', 1)->run();
```
```SQL
UPDATE users SET name = 'John Doe' WHERE id = 1
```

<br>

### Delete

The `QB::delete()` method is used to create a `DELETE` query. The `delete()` method accepts two arguments as the table name and the alias of the table.

> You can also use alternative methods like `deleteFrom()`. They are the same.

> You can also use `as()` method to set the alias of the table.

```php
QB::delete('users')->where('id', 1);
```
```SQL
DELETE FROM users WHERE id = 1
```

> You can use **Model** classes instead of table names.

<br>

> To use `where()` method with `delete()` method, please see the [Where](#where) section.

> **Note:** you <span style="color: red;">cannot</span> use `delete()` method without using `where()` method.

<br>

To execute the query, you can use the `run()` or `execute()` methods.

```php
QB::delete('users')->where('id', 1)->run();
```
```SQL
DELETE FROM users WHERE id = 1
```

<br>

> You can get SQL query as the string by ending the query with `raw()` method in `SELECT`, `INSERT`, `UPDATE` and `DELETE` queries.

<br>

## License

This project is open-sourced software licensed under the MIT License - see the [LICENSE](LICENSE) file for details.