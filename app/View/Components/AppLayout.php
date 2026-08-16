<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        // Luong: Tra ve view de hien thi giao dien cho request.
        return view('layouts.app');
    }
}
