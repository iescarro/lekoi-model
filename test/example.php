<?php

require_once(__DIR__ . '/../vendor/autoload.php');

Util\load_env('./.env');

use Lekoi\DB;
use Lekoi\Model;

DB::init([
    'driver' => getenv('DB_CONNECTION'),
    'dbname' => getenv('DB_DATABASE')
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
$product->update(['title' => 'QWERTY'], 8);
$product->delete(1);

$products = $product->find_all(); // DB::get('products')->result();
echo "\n\n";
foreach ($products as $product) {
    echo "ID: $product->id, Product: $product->title, Price: $product->price\n";
}
