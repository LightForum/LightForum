<?php
/**
 * 修改generatePagination函数以支持伪静态URL
 */
function generatePagination($current_page, $total_pages, $url_pattern) {
    $html = '<div class="pagination">';
    
    // 上一页
    if ($current_page > 1) {
        $html .= '<a href="' . sprintf($url_pattern, $current_page - 1) . '" class="page-link">&laquo; 上一页</a>';
    } else {
        $html .= '<span class="page-link disabled">&laquo; 上一页</span>';
    }
    
    // 页码
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    if ($start > 1) {
        $html .= '<a href="' . sprintf($url_pattern, 1) . '" class="page-link">1</a>';
        if ($start > 2) {
            $html .= '<span class="page-link dots">...</span>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            $html .= '<span class="page-link active">' . $i . '</span>';
        } else {
            $html .= '<a href="' . sprintf($url_pattern, $i) . '" class="page-link">' . $i . '</a>';
        }
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= '<span class="page-link dots">...</span>';
        }
        $html .= '<a href="' . sprintf($url_pattern, $total_pages) . '" class="page-link">' . $total_pages . '</a>';
    }
    
    // 下一页
    if ($current_page < $total_pages) {
        $html .= '<a href="' . sprintf($url_pattern, $current_page + 1) . '" class="page-link">下一页 &raquo;</a>';
    } else {
        $html .= '<span class="page-link disabled">下一页 &raquo;</span>';
    }
    
    $html .= '</div>';
    
    return $html;
}
