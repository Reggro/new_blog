<?php namespace App\Http\Controllers;

use App\Posts;
use App\User;
use Redirect;
use App\Http\Controllers\Controller;
use App\Http\Requests\PostFormRequest;
use Illuminate\Http\Request;

use Illuminate\Http\Request;

class PostController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		//fetch 5 posts from database which are active and latest
		$posts = Posts::where('active',1)->orderBy('created_at','desc')->paginate(5);
		//page heading
		$title = 'Latest Posts';
		//return home.blade.php template from resources/views folder
		return view('home')->withPosts($posts)->withTitle($title);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create(Request $request)
	{
		// if user can post i.e. user is admin or author
		if($request->user()->can_post())
		{
			return view('posts.create');
		}
		else
		{
			return redirect('/')->withErrors('You have not sufficient permissions for writing post');
		}
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store(PostFormRequest $request)
	{
		$post = new Posts();
		$post->title = $request->get('title');
		$post->body = $request->get('body');
		$post->slug = str_slug($post->title);
		$post->author_id = $request->user()->id;
		if($request->has('save'))
		{
			$post->active = 0;
			$message = 'Post saved successfully';
		}
		else
		{
			$post->active = 1;
			$message = 'Post published successfully';
		}
		$post->save();
		return redirect('edit/'.$post->slug)->withMessage($message);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit(Request $request,$slug)
	{
		$post = Posts::where('slug',$slug)->first();
		if($post && ($request->user()->id == $post->author_id || $request->user()->is_admin()))
			return view('posts.edit')->with('post',$post);
		return redirect('/')->withErrors('you have not sufficient permissions');
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update(Request $request)
	{
		//
		$post_id = $request->input('post_id');
		$post = Posts::find($post_id);
		if($post && ($post->author_id == $request->user()->id || $request->user()->is_admin()))
		{
			$title = $request->input('title');
			$slug = str_slug($title);
			$duplicate = Posts::where('slug',$slug)->first();
			if($duplicate)
			{
				if($duplicate->id != $post_id)
				{
					return redirect('edit/'.$post->slug)->withErrors('Title already exists.')->withInput();
				}
				else
				{
					$post->slug = $slug;
				}
			}
			$post->title = $title;
			$post->body = $request->input('body');
			if($request->has('save'))
			{
				$post->active = 0;
				$message = 'Post saved successfully';
				$landing = 'edit/'.$post->slug;
			}
			else {
				$post->active = 1;
				$message = 'Post updated successfully';
				$landing = $post->slug;
			}
			$post->save();
			return redirect($landing)->withMessage($message);
		}
		else
		{
			return redirect('/')->withErrors('you have not sufficient permissions');
		}
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy(Request $request, $id)
	{
		//
		$post = Posts::find($id);
		if($post && ($post->author_id == $request->user()->id || $request->user()->is_admin()))
		{
			$post->delete();
			$data['message'] = 'Post deleted Successfully';
		}
		else
		{
			$data['errors'] = 'Invalid Operation. You have not sufficient permissions';
		}
		return redirect('/')->with($data);
	}

}
