<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Book::class);

        $books = Book::query()
            ->when($request->has('title'), function ($query) use ($request) {
                $query->where('title', 'like', '%'.$request->input('title').'%');
            })
            ->when($request->has('isbn'), function ($query) use ($request) {
                $query->where('ISBN', 'like', '%'.$request->input('isbn').'%');
            })
            ->when($request->has('is_available'), function ($query) use ($request) {
                $query->where('is_available', $request->boolean('is_available'));
            })
            ->paginate();

        return response()->json(BookResource::collection($books));
    }

    public function show(Book $book)
    {
        $this->authorize('view', $book);

        return response()->json(BookResource::make($book));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Book::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'ISBN' => ['required', 'string', 'max:255', 'unique:books,ISBN'],
            'total_copies' => ['required', 'integer', 'min:1'],
            'available_copies' => ['nullable', 'integer', 'min:0'],
        ]);

        $availableCopies = $validated['available_copies'] ?? $validated['total_copies'];

        $book = Book::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'ISBN' => $validated['ISBN'],
            'total_copies' => $validated['total_copies'],
            'available_copies' => min($availableCopies, $validated['total_copies']),
            'is_available' => $availableCopies > 0,
        ]);

        return response()->json(BookResource::make($book), 201);
    }

    public function update(Request $request, Book $book)
    {
        $this->authorize('update', $book);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:1000'],
            'ISBN' => ['sometimes', 'string', 'max:255', 'unique:books,ISBN,'.$book->id],
            'total_copies' => ['sometimes', 'integer', 'min:1'],
            'available_copies' => ['sometimes', 'integer', 'min:0'],
        ]);

        if (array_key_exists('total_copies', $validated) && array_key_exists('available_copies', $validated)) {
            $validated['available_copies'] = min($validated['available_copies'], $validated['total_copies']);
        }

        $book->update($validated);

        if (array_key_exists('available_copies', $validated) || array_key_exists('total_copies', $validated)) {
            $book->update([
                'is_available' => $book->available_copies > 0,
            ]);
        }

        return response()->json(BookResource::make($book->fresh()));
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->json([], 204);
    }
}
