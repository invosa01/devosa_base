<?php
include_once("treeview.php");
$tv = new clsTreeView("treeView1", "./");
//root
$t1 = $tv->addNode(new TreeNode("test-1"));
$t2 = $tv->addNode(new TreeNode("test-2"));
//child level1
$tv->addNode(new TreeNode("test-1-1"), $t1);
$t3 = $tv->addNode(new TreeNode("test-1-2"), $t1);
$tv->addNode(new TreeNode("test-1-3"), $t1);
$tv->addNode(new TreeNode("test-1-4"), $t1);
$tv->addNode(new TreeNode("test-2-1"), $t2);
$tv->addNode(new TreeNode("test-2-2"), $t2);
$tv->addNode(new TreeNode("test-1-1-1"), $t3);
$tv->addNode(new TreeNode("test-1-1-2"), $t3);
echo $tv->render();
?>