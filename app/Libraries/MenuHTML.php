<?php

namespace App\Libraries;

class MenuHTML
{

    /**
     * Prints a recursive menu structure as HTML.
     *
     * @param array $menus The menu array structure.
     * @param bool $hasParent Whether the current level has a parent.
     * @param object|null $currentMenu The current active menu.
     * @return string The generated HTML string.
     */
    public static function render(array $menus, bool $hasParent = false, $currentMenu = null): string
    {
        $string = $hasParent ? '<ul class="ml-4 nav nav-treeview">' : '';
        $url = env('frontend.baseURL');

        foreach ($menus as $menu) {
            if ((count($menu['child']) > 0 || $menu['link'] != '' || $menu['aco_id'] != 0) && self::hasClickableChild($menu)) {
                if ($menu['menuname'] == "DASHBOARD") {
                    $menu['class'] = "dashboard";
                }

                $linkHref = count($menu['child']) > 0
                    ? 'javascript:void(0)'
                    : ($menu['link'] != '' ? strtolower($url . $menu['link']) : strtolower($url . $menu['menuexe']));

                $isActive = (isset($currentMenu->id) && $currentMenu->id == $menu['menuid']) ? 'active hover' : '';
                $icon = strtolower($menu['menu_icon']) ?? 'far fa-circle';
                $linkId = count($menu['child']) > 0 ? '' : 'link-' . $menu['class'];
                $childIcon = count($menu['child']) > 0 ? '<i class="right fas fa-angle-left"></i>' : '';
                $childMenu = count($menu['child']) > 0 ? self::render($menu['child'], true, $currentMenu) : '';

                $string .= '
                <li class="nav-item">
                  <a id="' . $linkId . '" href="' . $linkHref . '" class="nav-link ' . $isActive . '">
                    <i class="nav-icon ' . $icon . '"></i>
                    <p>
                      ' . $menu['menuname'] . '
                      ' . $childIcon . '
                    </p>
                  </a>
                  ' . $childMenu . '
                </li>
              ';
            }
        }

        $string .= $hasParent ? '</ul>' : '';
        return $string;
    }

    private static function hasClickableChild(array $menu): bool
    {
        if (count($menu['child']) > 0) {
            foreach ($menu['child'] as $menuChild) {
                if (self::hasClickableChild($menuChild)) {
                    return true;
                }
            }
        } else {
            return self::isClickableChild($menu);
        }

        return false;
    }

    private static function isClickableChild(array $menu): bool
    {
        if ($menu['menu_parent'] == 0) {
            return true;
        } else {
            return $menu['aco_id'] != 0 && $menu['menuexe'] != '/';
        }
    }
}
