<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //get all posts
        $posts = Menu::latest()->get(); // Added ->get() to execute the query and retrieve the posts

        //return collection of posts as a resource
        return new PostResource(true, 'List Data menu', $posts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //define validation rules
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5000',
            'name_menu' => 'required|string|max:255', // Added string and max length validation
            'price' => 'required|numeric', // Added numeric validation
            'category' => 'required|string|max:255', // Added string and max length validation
            'stock' => 'required|integer', // Added integer validation
            'ratings' => 'required|numeric|min:0|max:5', // Added numeric validation and range
            'description' => 'required|string', // Added string validation
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        //upload image
        $image = $request->file('image');
        $imageName = time().'.'.$image->extension();
        $image->move(public_path('menu'), $imageName);

        //create post
        $post = Menu::create([
            'image' => $imageName,
            'name_menu' => $request->name_menu,
            'price' => $request->price,
            'category' => $request->category,
            'stock' => $request->stock,
            'ratings' => $request->ratings,
            'description' => $request->description,
        ]);

        //return response
        return new PostResource(true, 'Data Menu Berhasil Ditambahkan!', $post);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //find post by ID
        $post = Menu::find($id);

        //check if post exists
        if (is_null($post)) {
            return response()->json(['message' => 'Menu not found x show'], 404);
        }

        //return single post as a resource
        return new PostResource(true, 'Detail Data Menu', $post);
    }

    /**
     * update
     *
     * @param  mixed  $request
     * @param  mixed  $post
     * @return void
     */
    public function update(Request $request, $id)
    {
        //define validation rules
        $validator = Validator::make($request->all(), [
            'name_menu' => 'required|string|max:255', // Added string and max length validation
            'price' => 'required|numeric', // Added numeric validation
            'category' => 'required|string|max:255', // Added string and max length validation
            'stock' => 'required|integer', // Added integer validation
            'description' => 'required|string', // Added string validation
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        //find post by ID
        $post = Menu::find($id);

        //check if image is not empty
        if ($request->hasFile('image')) {

            //upload image
            $image = $request->file('image');
            $image->storeAs('public/image', $image->hashName());

            //delete old image
            Storage::delete('public/image/'.basename($post->image));

            //update post with new image
            $post->update([
                'image' => $image->hashName(),
                'name_menu' => $request->name_menu,
                'price' => $request->price,
                'category' => $request->category, // Added 'category' field
                'stock' => $request->stock,
                'description' => $request->description, // Added 'description' field
            ]);

        } else {

            //update post without image
            $post->update([
                'name_menu' => $request->name_menu,
                'price' => $request->price,
                'category' => $request->category,
                'stock' => $request->stock,
                'description' => $request->description,
            ]);
        }

        //return response
        return new PostResource(true, 'Data Menu Berhasil Diubah!', $post);
    }

    /**
     * destroy
     *
     * @param  mixed  $post
     * @return void
     */
    public function destroy($id)
    {

        //find post by ID
        $post = Menu::find($id);

        //delete image
        Storage::delete('public/image/'.basename($post->image));

        //delete post
        $post->delete();

        //return response
        return new PostResource(true, 'Data Post Berhasil Dihapus!', null);
    }

    /**
     * Search for a menu item by name.
     *
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $searchTerm = $request->query('q');
        \Log::info('Search Term: '.$searchTerm); // Log the search term

        $posts = Menu::where('name_menu', 'LIKE', '%'.$searchTerm.'%')->get();
        \Log::info('Search Results: ', $posts->toArray()); // Log the search results

        if ($posts->isEmpty()) {
            \Log::info('No Menu found for: '.$searchTerm);

            return response()->json(['message' => 'Menu not found for search term: '.$searchTerm], 404);
        }

        \Log::info('Menu found: '.$posts->count());

        return response()->json(['success' => true, 'message' => 'Search Results: '.$searchTerm, 'data' => $posts]);
    }

    /**
     * Display a listing of the resource filtered by category.
     *
     * @param  string  $category
     * @return \Illuminate\Http\Response
     */
    public function filterByCategory($category)
    {
        // Retrieve posts filtered by category
        $posts = Menu::where('category', $category)->latest()->get();

        // Return collection of posts as a resource
        return new PostResource(true, 'Filtered Data menu by Category', $posts);
    }
}
