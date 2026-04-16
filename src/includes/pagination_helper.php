<?php

/**
 * 修改generatePagination函数以支持伪静态URL
 */
function generatePagination($current_page, $total_pages, $url_pattern)
{
    $html = '<ul class="pagination justify-content-center">';

    // 上一页
    if ($current_page > 1) {
        $html .= '<li class="page-item"><a href="' . sprintf($url_pattern, $current_page - 1) . '" class="page-link">&laquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link disabled">&laquo;</span></li>';
    }

    // 页码
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);

    if ($start > 1) {
        $html .= '<li class="page-item"><a href="' . sprintf($url_pattern, 1) . '" class="page-link">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item"><span class="page-link dots">...</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            $html .= '<li class="page-item"><span class="page-link active">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a href="' . sprintf($url_pattern, $i) . '" class="page-link">' . $i . '</a></li>';
        }
    }

    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= '<li class="page-item"><span class="page-link dots">...</span></li>';
        }
        $html .= '<li class="page-item"><a href="' . sprintf($url_pattern, $total_pages) . '" class="page-link">' . $total_pages . '</a></li>';
    }

    // 下一页
    if ($current_page < $total_pages) {
        $html .= '<li class="page-item"><a href="' . sprintf($url_pattern, $current_page + 1) . '" class="page-link">&raquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link disabled">&raquo;</span></li>';
    }

    $html .= '</ul>';

    return $html;
}
