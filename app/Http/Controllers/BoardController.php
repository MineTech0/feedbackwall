<?php

namespace App\Http\Controllers;

use App\Services\BoardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BoardController extends Controller
{
    public function __construct(
        protected BoardService $boardService
    ) {}

    public function index()
    {
        return Inertia::render('Board/Index', [
            'boards' => $this->boardService->getActiveBoards()
        ]);
    }

    public function show(Request $request, $slug)
    {
        $board = $this->boardService->getBoardBySlug($slug);
        
        $sort = $request->query('sort', 'top');
        $data = $this->boardService->getBoardFeedback($board, $sort, $request);

        return Inertia::render('Board/Show', [
            'board' => $board,
            'feedback' => $data['feedback'],
            'votedIds' => $data['votedIds'],
            'sort' => $sort,
        ]);
    }
}
