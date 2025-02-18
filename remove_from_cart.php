<?php
session_start();

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Remove the bike with the specified ID
    $_SESSION['cart'] = array_filter($_SESSION['cart'], function ($bike) use ($id) {
        return $bike['id'] != $id;
    });

    header("Location: add_to_cart.php?message=Bike removed from cart");
}
