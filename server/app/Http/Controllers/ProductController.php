<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Image;
use App\Models\Thumbnail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    //
    public function index()
    {
        // get all products
        $products = DB::table('products')
                    ->join('thumbnails', 'products.id', '=', 'thumbnails.product_id')
                    ->select('products.*', 'thumbnails.thumbnail')
                    ->get();

        // Append the full URL to the thumbnail
        $products = $products->map(function ($product) {
            $product->thumbnail = asset('storage/' . $product->thumbnail);
            return $product;
        });

        return $products;
    }

    public function store(Request $request)
    {
        // validate request data
        $fields = $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required',
            'category' => 'required|string',
            'brand' => 'required|string',
            'shipping' => 'boolean',
            'sku' => 'string',
        ]);

        // get the request images
        if ($request->has('images')) {
            // store the data in the products table
            $product = Product::create($fields);
            $id = $product->id;

            // store the thumbnail locally
            $thumbnail = $request->file('thumbnail');
            $tnName = $id.'_thumbnail_'.time().rand(1, 1000).'.'.$thumbnail->extension();
            $thumbnail->storeAs('public/products/'.$id, $tnName, 'local');

            // store the thumbnail in thumbnails table
            $t = new Thumbnail();
            $t->product_id = $id;
            $t->thumbnail = 'products/'.$id.'/'.$tnName;
            $t->save();

            // store the thumbnail in images table
            $image = new Image();
            $image->product_id = $id;
            $image->image = 'products/'.$id.'/'.$tnName;
            $image->save();

            $images = $request->file('images');

            // loop through the images
            foreach ($images as $image) {
                // store the images locally
                $imageName = $id.'_image_'.time().rand(1,1000).'.'.$image->extension();
                $image->storeAs('public/products/'.$id, $imageName, 'local');

                // store the images in the images table
                $newImage = new Image();
                $newImage->product_id = $id;
                $newImage->image = 'products/'.$id.'/'.$imageName;
                $newImage->save();
            }
        }

        // Append the full URL to the thumbnail in the response
        $product->thumbnail = asset('storage/' . $product->thumbnail);

        $response = [
            'message' => 'Product created',
            'product' => $product,
        ];

        return response($response, 201);
    }

    // GET 1 Product Function
    public function getProduct($id) {
        $product = Product::with('images', 'thumbnail')->find($id);

        if (!$product) {
            return response(['message' => 'No Product with the ID: ' . $id], 404);
        }

        // Format the thumbnail URL
        $product->thumbnail = asset('storage/' . $product->thumbnail->thumbnail);

        // Format the images URLs
        $product->images->transform(function ($image) { 
            $image->image = asset('storage/' . $image->image);
            return $image;
        });

        return response($product, 200);
    }

    public function update(Request $request)
    {

        // return Product::update($request->all());
        return Product::find($request->id)->update($request->all());

    }

    public function destroy($id)
    {
        $path = 'products/'.$id;
        Storage::disk('s3')->deleteDirectory($path);

        return Product::destroy($id);

        // return Product::find($id)->delete();

        // $product = Product::find($id);
        // $product->delete();
    }
}
