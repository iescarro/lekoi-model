<?php

require_once(__DIR__ . '/../vendor/autoload.php');

Util\load_env('./.env');

use Lekoi\DB;
use Lekoi\Model;

DB::init([
    'driver' => 'mysqli', //getenv('DB_CONNECTION'),
    'host' => getenv('DB_HOST'),
    'port' => getenv('DB_PORT'),
    'dbname' => getenv('DB_DATABASE'),
    'username' => getenv('DB_USERNAME'),
    'password' => getenv('DB_PASSWORD')
]);

class Product extends Model
{
    function save($user)
    {
        $this->db->insert('products', $user);
        // return $this->db->insert_id();
    }

    function read($id)
    {
        return $this->db->get('products', array('id' => $id))->row();
    }

    function find_all()
    {
        return $this->db->get('products')->result();
    }

    function update($product, $id)
    {
        $this->db->update('products', $product, array('id' => $id));
    }

    function delete($id)
    {
        $this->db->delete('products', array('id' => $id));
    }
}

$product = new Product();
$product->save(['title' => 'ASDF', 'price' => rand(15, 50)]);

$o = $product->read(17);
var_dump($o);

$product->update(['title' => 'QWERTY'], 7);
$product->delete(2);

DB::delete('products', ['id' => 10]);
DB::update('products', ['title' => 'Some weird title'], ['id' => 15]);

$products = $product->find_all(); // DB::get('products')->result();
echo "\n\n";
foreach ($products as $product) {
    echo "ID: $product->id, Product: $product->title, Price: $product->price\n";
}
