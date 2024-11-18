<?php
// 应用公共文件

function convertMetersToInches($meters) {
    return $meters * 39.3701;
}

function convertInchesToPixels($inches, $dpi) {
    return $inches * $dpi;
}

function convertMetersToPixels($meters, $dpi=150) {
    $inches = convertMetersToInches($meters);
    return intval(convertInchesToPixels($inches, $dpi));
}

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
