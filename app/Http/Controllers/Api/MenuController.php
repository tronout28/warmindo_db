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
     // Existing methods...

     public function index()
     {
         $posts = Menu::latest()->get();
         return new PostResource(true, 'List Data menu', $posts);
     }
 
     public function store(Request $request)
     {
         $validator = Validator::make($request->all(), [
             'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5000',
             'name_menu' => 'required|string|max:255',
             'price' => 'required|numeric',
             'category' => 'required|string|max:255',
             'second_category' => 'nullable|string|max:255',
             'stock' => 'required|integer',
             'ratings' => 'required|numeric|min:0|max:5',
             'description' => 'required|string',
         ]);
 
         if ($validator->fails()) {
             return response()->json($validator->errors(), 422);
         }
 
         $image = $request->file('image');
         $imageName = time().'.'.$image->extension();
         $image->move(public_path('menu'), $imageName);
 
         $post = Menu::create([
             'image' => $imageName,
             'name_menu' => $request->name_menu,
             'price' => $request->price,
             'category' => $request->category,
             'second_category' => $request->second_category,
             'stock' => $request->stock,
             'ratings' => $request->ratings,
             'description' => $request->description,
         ]);
 
         return new PostResource(true, 'Data Menu Berhasil Ditambahkan!', $post);
     }
 
     public function show($id)
     {
         $post = Menu::find($id);
         if (is_null($post)) {
             return response()->json(['message' => 'Menu not found'], 404);
         }
         return new PostResource(true, 'Detail Data Menu', $post);
     }
 
     public function update(Request $request, $id)
     {
         $validator = Validator::make($request->all(), [
             'name_menu' => 'required|string|max:255',
             'price' => 'required|numeric',
             'category' => 'required|string|max:255',
             'second_category' => 'nullable|string|max:255',
             'stock' => 'required|integer',
             'description' => 'required|string',
         ]);
 
         if ($validator->fails()) {
             return response()->json($validator->errors(), 422);
         }
 
         $post = Menu::find($id);
 
         if ($request->hasFile('image')) {
             $image = $request->file('image');
             $imageName = time().'.'.$image->extension();
             $image->move(public_path('menu'), $imageName);
 
             Storage::delete('public/image/'.basename($post->image));
 
             $post->update([
                 'image' => $imageName,
                 'name_menu' => $request->name_menu,
                 'price' => $request->price,
                 'category' => $request->category,
                 'second_category' => $request->second_category,
                 'stock' => $request->stock,
                 'description' => $request->description,
             ]);
         } else {
             $post->update([
                 'name_menu' => $request->name_menu,
                 'price' => $request->price,
                 'category' => $request->category,
                 'second_category' => $request->second_category,
                 'stock' => $request->stock,
                 'description' => $request->description,
             ]);
         }
 
         return new PostResource(true, 'Data Menu Berhasil Diubah!', $post);
     }
 
     public function destroy($id)
     {
         $post = Menu::find($id);
 
         Storage::delete('public/image/'.basename($post->image));
 
         $post->delete();
 
         return new PostResource(true, 'Data Menu Berhasil Dihapus!', null);
     }
 
     public function search(Request $request)
     {
         $searchTerm = $request->query('q');
         \Log::info('Search Term: '.$searchTerm);
 
         $posts = Menu::where('name_menu', 'LIKE', '%'.$searchTerm.'%')->get();
         \log::info('Search Results: ', $posts->toArray());
 
         if ($posts->isEmpty()) {
             \Log::info('No Menu found for: '.$searchTerm);
             return response()->json(['message' => 'Menu not found for search term: '.$searchTerm], 404);
         }
 
         \Log::info('Menu found: '.$posts->count());
 
         return response()->json(['success' => true, 'message' => 'Search Results: '.$searchTerm, 'data' => $posts]);
     }
 
     public function filterByCategory($category)
     {
         $posts = Menu::where('category', $category)->latest()->get();
         return new PostResource(true, 'Filtered Data menu by Category', $posts);
     }
 
     // Add the new method here
     public function filterBySecondCategory($second_category)
     {
         $posts = Menu::where('second_category', $second_category)->latest()->get();
         return new PostResource(true, 'Filtered Data menu by Second Category', $posts);
     }
}
