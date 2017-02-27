<?php
/*
   Dedy's class Treeview
    Adapted from Yahoo UI js
   version 1.0
   PT. Invosa Systems
   All right reserved.
*/
require_once("treeview.config.php");

class clsTreeView
{

  var $dataNode;

  var $idSequence;

  var $name;

  var $path;

  // Class constructor

  function clsTreeView($name, $path = "")
  {
    global $TREEVIEW_CLASS_PATH;
    $this->name = $name;
    //$this->data = $data;
    if ($path == "") {
      $path = $TREEVIEW_CLASS_PATH;
    }
    $this->dataNode = [];
    $this->path = $path;
    $this->idSequence = 0;
  }

  function addNode($node, $parentNode = null)
  {
    if (is_a($node, 'TreeNode')) {
      $this->idSequence++;
      if ($parentNode != null && is_a($parentNode, 'TreeNode')) {
        $node->hasParent = true;
        $node->parentId = $parentNode->nodeId;
        $this->dataNode[$parentNode->nodeId]->hasChild = true;
      } else {
        $node->hasParent = false;
      }
      $node->nodeId = $this->idSequence;
      $this->dataNode[$this->idSequence] = $node;
      return $node;
    } else {
      return null;
    }
  }

  function printCSS()
  {
    $strResult = "
<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $this->path . "stylesheet/screen.css\" />
<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $this->path . "stylesheet/tree.css\" />";
    return $strResult;
  }

  function printData()
  {
    $strResult = "";
    $strResult .= "
<script type=\"text/javascript\">
  var tree;
  function treeInit() {
    tree = new YAHOO.widget.TreeView(\"" . $this->name . "\");";
    foreach ($this->dataNode as $node) {
      if ($node->isExpand) {
        $isExpand = 'true';
      } else {
        $isExpand = 'false';
      }
      if ($node->hRef != "") {
        $strResult .= "var data = {id: " . $node->nodeId . ", label: \"" . $node->label . "\",  href: \"" . $node->hRef . "\"};\n";
        if (!$node->hasParent) //root
        {
          $strResult .= "tmpNode_" . $node->nodeId . " = new YAHOO.widget.TextNode(data, tree.getRoot(), " . $isExpand . ");\n";
        } else {
          $strResult .= "tmpNode_" . $node->nodeId . " = new YAHOO.widget.TextNode(data, tmpNode_" . $node->parentId . ", " . $isExpand . ");\n";
        }
      } else {
        if (!$node->hasParent) //root
        {
          $strResult .= "tmpNode_" . $node->nodeId . " = new YAHOO.widget.TextNode(\"" . $node->label . "\", tree.getRoot(), " . $isExpand . ");\n";
        } else {
          $strResult .= "tmpNode_" . $node->nodeId . " = new YAHOO.widget.TextNode(\"" . $node->label . "\", tmpNode_" . $node->parentId . ", " . $isExpand . ");\n";
        }
      }
    }
    $strResult .= "tree.draw();
  }
  treeInit();
</script>";
    return $strResult;
  }

  function printJavascript()
  {
    $strResult = "
<script type=\"text/javascript\" src=\"" . $this->path . "scripts/yahoo.js\" ></script>
<script type=\"text/javascript\" src=\"" . $this->path . "scripts/event.js\"></script>
<script type=\"text/javascript\" src=\"" . $this->path . "scripts/treeview.js\" ></script>";
    return $strResult;
  }

  function render()
  {
    $strResult = "";
    $strResult .= $this->printCSS();
    $strResult .= $this->printJavascript();
    $strResult .= "
<div id=\"divTV_" . $this->name . "\">
  <a href=\"javascript:tree.expandAll()\">" . getWords('expand all') . "</a>
  <a href=\"javascript:tree.collapseAll()\">" . getWords('collapse all') . "</a>
</div>";
    $strResult .= "
<div id=\"" . $this->name . "\"></div>";
    $strResult .= $this->printData();
    return $strResult;
  }
}

class TreeNode
{

  var $hRef;

  var $hasChild = false;

  //provide hRef link here if any
  //e.g: javascript:editData()

  var $hasParent = false;

  var $isExpand;

  var $label;

  var $nodeId;

  var $parentId = 0;

  function TreeNode($label, $hRef = "", $isExpand = false)
  {
    $this->label = $label;
    $this->hRef = $hRef;
    $this->isExpand = $isExpand;
  }
}

?>