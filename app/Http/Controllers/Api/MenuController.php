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

     public function index(Request $request)
    {
        $menus = Menu::with('ratings')->get(); // No need to manually add average_rating

        return response()->json($menus);
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
             'rating' => $request->rating,
             'description' => $request->description,
         ]);
         
         return response([
            'status' => 'success',
            'message' => 'Data Menu Berhasil Ditambahkan!',
            'data' => $post
         ]);
     }

     
 
        public function show($id, Request $request)
        {
            $menu = Menu::findOrFail($id);
            $userId = $request->query('user_id'); // Get user_id from query parameters
            $averageRating = $menu->averageRating($userId);

            $formattedAverageRating = number_format($averageRating, 1);

            return response()->json([
                'menu_item' => $menu,
                'average_rating' => $formattedAverageRating,
                'user_id' => $userId,
            ]);
        }
    public function update(Request $request, $id)
    {
        // Validate incoming request
        $validatedData = $request->validate([
            'name_menu' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'category' => 'nullable|string|max:255',
            'second_category' => 'nullable|string|max:255',
            'stock' => 'nullable|integer',
            'description' => 'nullable|string',
            'status_menu' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate image
        ]);
    
        // Find the menu item
        $menu = Menu::find($id);
    
        if (is_null($menu)) {
            return response()->json(['message' => 'Menu item not found'], 404);
        }
    
        // Handle image upload 
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time().'.'.$image->extension();
            $image->move(public_path('menu'), $imageName);
    
            // Update the image path in the validated data
            $validatedData['image'] = '' . $imageName;
    
            // Optionally delete the old image if it exists
            if ($menu->image && file_exists(public_path($menu->image))) {
                unlink(public_path($menu->image));
            }
        }
    
        // Update the menu item with validated data
        $menu->update($validatedData);
    
        return response()->json([
            'status' => 'success',
            'message' => 'Data Menu Berhasil Diubah!',
            'menu' => $menu,
        ], 200);
    }
    
    
    //  public function update(Request $request, $id)
    //  {
    //     $validator = $request->validate([
    //         'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5000',
    //         'name_menu' => 'required|string|max:255',
    //         'price' => 'required|numeric',
    //         'category' => 'required|string|max:255',
    //         'second_category' => 'nullable|string|max:255',
    //         'stock' => 'required|integer',
    //         'description' => 'required|string',
    //     ]);

    //      $post = Menu::find($id);
 
    //      if ($request->hasFile('image')) {
    //          $image = $request->file('image');
    //          $imageName = time().'.'.$image->extension();
    //          $image->move(public_path('menu'), $imageName);
    //          Storage::delete('public/image/'.basename($post->image));
    //          $post->update([$validator]);
    //      } else {
    //          $post->update([
    //              $validator
    //          ]);
    //      }
 
    //     
    //  }
 
     public function destroy($id)
     {
         $post = Menu::find($id);
 
         Storage::delete('public/image/'.basename($post->image));
 
         $post->delete();
 
         return new PostResource(true, 'Data Menu Berhasil Dihapus!', null);
     }

     public function disableMenu($id)
     {
         $post = Menu::find($id);
         $post->status_menu = false;
         $post->save();
 
         return new PostResource(true, 'Menu Disabled Successfully', $post);
     }

    public function enableMenu($id)
        {
            $post = Menu::find($id);
            $post->status_menu = true;
            $post->save();
    
            return new PostResource(true, 'Menu Enabled Successfully', $post);
        }
 
     public function search(Request $request)
     {
 
         $posts = Menu::where('name_menu', 'LIKE', '%'.$request->q.'%')->get();
 
         if ($posts->isEmpty()) {
             return response()->json(['message' => 'Menu not found for search term: '.$request->q], 404);
         }

 
         return response()->json(['success' => true, 'message' => 'Search Results: '.$request->q, 'data' => $posts]);
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
