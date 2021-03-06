<?php

/**
 * Handles all tasks related to retrieving and displaying products
 *
 * Class Products
 */
class Products
{
    private $Database;

    private $dbTable = 'products';

    function __construct()
    {
        global $Database;
        $this->Database = $Database;
    }

///////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////*Setters & Getters*///////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Retrieves product information from database
     * @param int $id
     * @return array
     */
    public function get($id = null)
    {
        $data = [];
        if (is_array($id)) {
            //get products based on array of ids
            $items = '';
            foreach ($id as $item) {
                if ($items != '') {
                    $items .= ',';
                }
                $items .= $item;
            }

            if (!empty($items) && $result = $this->Database->query("SELECT id, name, description, price, 
                                                        image FROM $this->dbTable WHERE id IN ($items) ORDER BY name")) {
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_array()) {
                        $data[] = [
                            'id' => $row['id'],
                            'name' => $row['name'],
                            'description' => $row['description'],
                            'price' => $row['price'],
                            'image' => $row['image'],
                            'quantity'=>$_SESSION['cart'][$row['id']]
                        ];
                    }
                }
            }
        } else if ($id != null) {
            //get one specific product
            if ($stmt = $this->Database->prepare("SELECT 
            $this->dbTable.id,
            $this->dbTable.name,
            $this->dbTable.description,
            $this->dbTable.price,
            $this->dbTable.image,
            categories.name AS category_name
            FROM $this->dbTable, categories
            WHERE $this->dbTable.id = ? AND $this->dbTable.category_id = categories.id")) {

                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->store_result();
                $stmt->bind_result($prod_id, $prod_name, $prod_description,
                    $prod_price, $prod_image, $cat_name);
                $stmt->fetch();

                if ($stmt->num_rows > 0) {
                    $data = ['id' => $prod_id, 'name' => $prod_name, 'description' => $prod_description,
                        'price' => $prod_price, 'image' => $prod_image, 'category_name' => $cat_name];
                }
                $stmt->close();
            }
        } else {
            //get all products
            if ($result = $this->Database->query("SELECT * FROM " . $this->dbTable . " ORDER BY name")) {
                while ($row = $result->fetch_array()) {
                    $data[] = [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'price' => $row['price'],
                        'image' => $row['image']
                    ];
                }
            }
        }
        return $data;
    }

    /**
     * Retrieve product information for all products in specific category
     * @param int $id
     * @return array
     */
    public function getInCategory($id)
    {
        $data = [];
        if ($stmt = $this->Database->prepare("SELECT id, name, price, image FROM "
            . $this->dbTable . " WHERE category_id = ? ORDER BY name")) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($prod_id, $prod_name, $prod_price, $prod_image);
            while ($stmt->fetch()) {
                $data[] = [
                    'id' => $prod_id,
                    'name' => $prod_name,
                    'price' => $prod_price,
                    'image' => $prod_image
                ];
            }
            $stmt->close();
        }
        return $data;
    }

    /**
     * Return an array of price info for specified ids
     *
     * @param array $ids
     * @return array
     */
    public function getPrices($ids)
    {
        $data = [];
        //create comma separated list
        $items = '';
        foreach ($ids as $id){
            if($items != ''){
                $items .=',';
            }
            $items .= $id;
        }

        //get multiple product info based on list of ids
        if($result = $this->Database->query("SELECT id, price FROM 
            $this->dbTable WHERE id IN ($items) ORDER BY name")){
            if($result->num_rows > 0){
                while ($row = $result->fetch_array()){
                    $data[] = [
                        'id'=>$row['id'],
                        'price'=>$row['price']
                    ];
                }
            }
        }
        return $data;
    }

    /**
     * Check to ensure that product exists
     * @param int $id
     * @return bool
     */
    public function productExists($id)
    {
        if ($stmt = $this->Database->prepare("SELECT id FROM $this->dbTable WHERE id = ?")) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($id);
            $stmt->fetch();

            if ($stmt->num_rows > 0) {
                $stmt->close();
                return true;
            }
            $stmt->close();
            return false;
        }
    }
///////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////*Create Page Elements*////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Create product table using info from database
     * @param int|null $cols
     * @param int|null $category
     * @return string
     */
    public function createProductTable($cols = 4, $category = null)
    {
        //get products
        if ($category != null) {
            //get products from specific category
            $products = $this->getInCategory($category);

        } else {
            $products = $this->get();
        }
        $data = '';
        //loop through each product
        if (!empty($products)) {
            $i = 1;
            foreach ($products as $product) {
                $data .= '<li ';
                if ($i == $cols) {
                    $data .= 'class="last"';
                    $i = 0;
                }
                $data .= '><a href="' . SITE_PATH . 'product.php?id=' . $product['id'] . '">';
                $data .= '<img src="' . IMAGE_PATH . $product['image'] . '"alt="' . $product['name'] . '"><br>';
                $data .= '<strong>' . $product['name'] . '</strong></a><br/>$' . $product['price'];
                $data .= '<br><a class="button_sml" href="' . SITE_PATH . 'cart.php?id=' . $product['id'] . '">
                            Add to Cart</a></li>';
                $i++;
            }
        }
        return $data;
    }
}