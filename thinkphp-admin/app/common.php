<?php
// 应用公共文件
use think\facade\Db;
function buildTree(array $elements, $parentId = 0) {
    $branch = [];

    foreach ($elements as $element) {
        if ($element['parentId'] == $parentId) {
            $children = buildTree($elements, $element['id']);
            if ($children) {
                $element['children'] = $children;
            }
            $branch[] = $element;
        }
    }

    return $branch;
}


function buildNodeMap($tree) {
    $nodeMap = [];
    foreach ($tree as $node) {
        $nodeMap[$node['id']] = $node;
    }
    return $nodeMap;
}

function collectParentNodes($nodeMap, $nodeId, &$parentNodes) {
    if (isset($nodeMap[$nodeId])) {
        $parentNodes[] = $nodeId;
        $parentId = $nodeMap[$nodeId]['parentId'];
        if ($parentId !== null) {
            collectParentNodes($nodeMap, $parentId, $parentNodes);
        }
    }
}

function filterTree($tree, $nodesToKeep) {
    $nodeMap = buildNodeMap($tree);
    $allNodesToKeep = [];

    foreach ($nodesToKeep as $nodeId) {
        collectParentNodes($nodeMap, $nodeId, $allNodesToKeep);
    }

    $filteredTree = array_filter($tree, function($node) use ($allNodesToKeep) {
        return in_array($node['id'], $allNodesToKeep);
    });

    return array_values($filteredTree); // 重新索引数组
}

function getChildIds($id, $categories) {
    $ids = [$id]; // 首先将传入的 ID 添加到结果数组中

    foreach ($categories as $category) {
        if ($category['parentId'] === $id) {
            $ids[] = $category['id']; // 添加当前子节点 ID
            // 递归获取子节点
            $ids = array_merge($ids, getChildIds($category['id'], $categories));
        }
    }

    return array_unique($ids);
}

function checkRoleHandle($handle,$role_Ids,$company_Id)
{
    Db::query("set @@sql_mode='';");
    $handle_detail=Db::name('system_menu_auth')
        ->alias('sma')
        ->join('system_menu sm', 'sm.Id = sma.menu_Id')
        ->join('system_role_handle srh', 'sma.Id = srh.menu_auth_Id')
        ->join('system_role sr', 'sr.id = srh.role_Id')
        ->whereIn('menu_auth_code',$handle)
        ->where('sr.company_Id', $company_Id)
        ->where('srh.is_delete', 0)
        ->group('sma.Id')
        ->field("sm.menu_name,sr.name,sm.menu_url,sma.menu_auth_code,sma.menu_auth_name,GROUP_CONCAT(srh.role_Id SEPARATOR ',') as role_Ids")
        ->select()->toArray();
    $role_Ids = explode(',', $role_Ids);
    $requestArray = [
        'menu_name' => '',
        'menu_url' => '',
        'menu_auth_code' => [],
        'menu_auth_name' => []
    ];
    $is_auth=false;
    foreach ($handle_detail as $key => &$value) {
        $value['role_Ids'] = explode(',', $value['role_Ids']);
        $a=array_intersect($role_Ids, $value['role_Ids']);
        if (!empty($a)) {
            $is_auth=true;
        }
        if (empty($newArray['menu_name'])) {
            $requestArray['menu_name'] = $value['menu_name'];
        }
        if (empty($newArray['menu_url'])) {
            $requestArray['menu_url'] = $value['menu_url'];
        }

        // 将权限代码和名称添加到对应的数组中
        $requestArray['menu_auth_code'][] = $value['menu_auth_code'];
        $requestArray['menu_auth_name'][] = $value['menu_auth_name'];
    }
    request()->menu_auth=$requestArray;
    request()->is_auth=$is_auth;
    if ($is_auth) {
        return true;
    }else{
        return false;
    }
}