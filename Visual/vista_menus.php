<!DOCTYPE html>
<html>
  <head>
    <?php
      include_once "vivian_common_header.php";
      include_once "vivian_tree_layout.css";
    ?>
    <!-- JQuery Buttons -->
    <script>
      $(function() {
        $( "button" ).button().click(function(event){
          event.preventDefault();
        });
        var test = [
                    {label:"eve: System Manager Menu", id:9},
                    {label:"test", id:1933},
                    {label:"test1", id:5038},
                    {label:"Combined A&MM Menus", id:2575}
                   ];
        $("#autocomplete").autocomplete({
          source: test,
          select: autoCompleteChanged,
          change: autoCompleteChanged
        }).val('eve').data('autocomplete')._trigger('select');
      });

      var btn = $.fn.button.noConflict() // reverts $.fn.button to jqueryui btn
      $.fn.btn = btn // assigns bootstrap button functionality to $.fn.btn

    </script>
    <?php include_once "vivian_google_analytics.php" ?>
  </head>

<body >
  <div>
    <?php include_once "vivian_osehra_image.php" ?>
    <!-- <select id="category"></select> -->
    <div style="font-size:10px; position:absolute; right:20px; top:40;">
      <button onclick="collapseAllNode()">Collapse All</button>
      <button onclick="resetAllNode()">Reset</button>
    </div>
    <div style="font-size:12px; position:absolute; right:20px; top:10px;">
      <label for="autocomplete">Select a top level menu: </label>
      <input id="autocomplete" size="30">
    </div>
  </div>
    <!-- Tooltip -->
  <div id="toolTip" class="tooltip" style="opacity:0;">
      <div id="head" class="header"></div>
      <div  class="tooltipTail"></div>
  </div>

  <div id="body" style="position: absolute; left:40px; top:50px">
  </div>
  <div id="dialog-modal">
    <div id='namespaces' style="display:none"></div>
    <div id='dependencies' style="display:none"></div>
    <div id="accordion">
        <h3><a href="#">Interfaces</a></h3>
        <div id="interface"></div>
        <h3><a href="#">Description</a></h3>
        <div id="description"></div>
    </div>
  </div>
<script type="text/javascript">
$("#accordion").accordion({heightStyle: 'content', collapsible: true}).hide();
var m = [10, 150, 10, 150],
    w = 1280*2 - m[1] - m[3],
    h = 1050*1 - m[0] - m[2],
    i = 0,
    root;

var selectedIndex = 0;

var tree = d3.layout.tree()
    .size([h, w]);

var diagonal = d3.svg.diagonal()
    .projection(function(d) { return [d.y, d.x]; });

var vis = d3.select("#body").append("svg:svg")
    .attr("width", w + m[1] + m[3])
    .attr("height", h + m[0] + m[2])
  .append("svg:g")
    .attr("transform", "translate(" + m[3] + "," + m[0] + ")");

function autoCompleteChanged(eve, ui) {
  console.log("label selected is " + ui.item.id);
  var menuFile = "menus/VistAMenu-" + ui.item.id + ".json";
  console.log("Menu file is " + menuFile);
  resetMenuFile(menuFile);
}

resetMenuFile("menus/VistAMenu-9.json");
function resetMenuFile(menuFile) {
  d3.json(menuFile, function(json) {
    root = json;
    root.x0 = h / 2;
    root.y0 = 0;
    resetAllNode();
  });
}

function toggleAll(d) {
  if (d.children) {
    d.children.forEach(toggleAll);
    toggle(d);
  }
}

function expandAllNode() {
  //root.children.forEach(toggleAll);
  expand(root)
  root.children.forEach(expandAll);
  update(root);
}

function collapseAllNode() {
  root.children.forEach(collapseAll);
  collapse(root)
  update(root);
}

function expandAll(d) {
  expand(d);
  if (d.children) {
    d.children.forEach(expandAll);
  }
}

function resetAllNode() {
  expand(root);
  root.children.forEach(collapseAll);
  // Initialize the display to show a few nodes.
  expandAll(root.children[0]);
  //root.children[0].forEach(toggleAll);
  //toggle(root.children[0]);
  //toggle(root.children[0].children[2]);
  //toggle(root.children[0].children[3]);
  //toggle(root.children[1]);
  //toggle(root.children[1].children[0]);
  //toggle(root.children[1].children[4]);
  //toggle(root.children[4]);
  //toggle(root.children[4].children[0]);
  update(root);
}

function collapseAll(d) {
  if (d.children) {
    d.children.forEach(collapseAll);
    collapse(d);
  }
}

// Collapse Node.
function collapse(d) {
  if (d.children) {
    d._children = d.children;
    d.children = null;
  }
}

// Expand children.
function expand(d) {
  if (d._children) {
    d.children = d._children;
    d._children = null;
  }
}

var package_link_url = "http://code.osehra.org/dox/Package_";
var toolTip = d3.select(document.getElementById("toolTip"));
var header = d3.select(document.getElementById("head"));

function update(source) {
  var duration = d3.event && d3.event.altKey ? 5000 : 500;

  // Compute the new tree layout.
  var nodes = tree.nodes(root).reverse();

  // Normalize for fixed-depth.
  nodes.forEach(function(d) { d.y = d.depth * 300; });

  // Update the nodes
  var node = vis.selectAll("g.node")
      .data(nodes, function(d) { return d.id || (d.id = ++i); });

  // Enter any new nodes at the parent's previous position.
  var nodeEnter = node.enter().append("svg:g")
      .attr("class", "node")
      .attr("transform", function(d) { return "translate(" + source.y0 + "," + source.x0 + ")"; })
      .on("mouseover", function(d) {
          node_onMouseOver(d);
      })
      .on("mouseout", function(d) {
          header.text("");
          toolTip.transition()
                 .duration(500)
                 .style("opacity", "0");
      });

  nodeEnter.append("svg:circle")
      .attr("r", 1e-6)
      .on("click", function(d) {
          toggle(d);
          update(d);
      })
      .style("fill", function(d) { return d._children ? "lightsteelblue" : "#fff"; });

  nodeEnter.append("svg:text")
      .attr("x", function(d) { return d.children || d._children ? -10 : 10; })
      .attr("dy", ".35em")
      .attr("text-anchor", function(d) { return d.children || d._children ? "end" : "start"; })
      .text(function(d) { return d.name; })
      .attr("fill", function(node){
        return change_node_color(node)
      })
      .attr("cursor", function(d){ return "pointer"})
      .style("fill-opacity", 1e-6)
      .on("mouseover", function(d) {
          node_onMouseOver(d);
      })
      .on("mouseout", function(d) {
          header.text("");
          toolTip.transition()
                 .duration(500)
                 .style("opacity", "0");
      })
      .on("click", function(d) {
        console.log("Node: " + d.name + " ien: " + d.ien + " clicked!");
        var optionLink = getOptionDetailLink(d)
        var win = window.open(optionLink, '_black');
        win.focus();
      });

  // Transition nodes to their new position.
  var nodeUpdate = node.transition()
      .duration(duration)
      .attr("transform", function(d) { return "translate(" + d.y + "," + d.x + ")"; });

  nodeUpdate.select("circle")
    .attr("r", function(d) {return 7 - d.depth/2;})
      .style("fill", function(d) { return change_circle_color(d); /* return d._children ? "lightsteelblue" : "#fff"; */ });

  nodeUpdate.select("text")
      .attr("fill", function(node){ return change_node_color(node) })
      .style("fill-opacity", 1);

  // Transition exiting nodes to the parent's new position.
  var nodeExit = node.exit().transition()
      .duration(duration)
      .attr("transform", function(d) { return "translate(" + source.y + "," + source.x + ")"; })
      .remove();

  nodeExit.select("circle")
      .attr("r", 1e-6);

  nodeExit.select("text")
      .style("fill-opacity", 1e-6);

  // Update the links
  var link = vis.selectAll("path.link")
      .data(tree.links(nodes), function(d) { return d.target.id; });

  // Enter any new links at the parent's previous position.
  link.enter().insert("svg:path", "g")
      .attr("class", "link")
      .attr("d", function(d) {
        var o = {x: source.x0, y: source.y0};
        return diagonal({source: o, target: o});
      })
    .transition()
      .duration(duration)
      .attr("d", diagonal);

  // Transition links to their new position.
  link.transition()
      .duration(duration)
      .attr("d", diagonal);

  // Transition exiting nodes to the parent's new position.
  link.exit().transition()
      .duration(duration)
      .attr("d", function(d) {
        var o = {x: source.x, y: source.y};
        return diagonal({source: o, target: o});
      })
      .remove();

  // Stash the old positions for transition.
  nodes.forEach(function(d) {
    d.x0 = d.x;
    d.y0 = d.y;
  });
}

function getPackageDoxLink(pkgName) {
  var doxLinkName = pkgName.replace(/ /g, '_').replace(/-/g, '_')
  return package_link_url + doxLinkName + ".html";
}

function getOptionDetailLink(node) {
  return "files/19-" + node.ien + ".html"
}

// Toggle children.
function toggle(d) {
  if (d.children) {
    d._children = d.children;
    d.children = null;
  } else {
    d.children = d._children;
    d._children = null;
  }
}

function change_node_color(node) {
  return "black";
}

function change_circle_color(d){
  if (d._children){
    return "lightsteelblue";
  }
  else {
    return "#fff";
  }
}

function node_onMouseOver(d) {
  var headText = "Option Name: " + d.option;
  if (d.lock !== undefined){
    headText = headText + "<br>" + "Security Key: " + d.lock + "</br>";
  }
  header.html(headText);
  toolTip.transition()
          .duration(200)
          .style("opacity", ".9");
  toolTip.style("left", (d3.event.pageX + 20) + "px")
          .style("top", (d3.event.pageY + 5) + "px");
}


    </script>
  </body>
</html>

