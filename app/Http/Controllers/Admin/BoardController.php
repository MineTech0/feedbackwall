<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBoardRequest;
use App\Http\Requests\Admin\UpdateBoardRequest;
use App\Models\Board;
use App\Services\BoardService;
use Inertia\Inertia;

class BoardController extends Controller
{
    public function __construct(
        protected BoardService $boardService
    ) {}

    public function index()
    {
        return Inertia::render('Admin/Boards/Index', [
            'boards' => $this->boardService->getAllBoards()
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Boards/Create');
    }

    public function store(StoreBoardRequest $request)
    {
        $this->boardService->createBoard($request->validated());

        return redirect()->route('admin.boards.index')->with('success', 'Board created successfully.');
    }

    public function edit(Board $board)
    {
        return Inertia::render('Admin/Boards/Edit', [
            'board' => $board
        ]);
    }

    public function update(UpdateBoardRequest $request, Board $board)
    {
        $this->boardService->updateBoard($board, $request->validated());

        return redirect()->route('admin.boards.index')->with('success', 'Board updated successfully.');
    }

    public function archive(Board $board)
    {
        $this->boardService->archiveBoard($board);
        return back()->with('success', 'Board archived.');
    }
}
