<?php
require_once __DIR__.'/layout-start.php';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['name'])){$name=trim($_POST['name']);$desc=trim($_POST['description']??'');if($name!==''){$st=$conn->prepare("INSERT INTO categories(name,description) VALUES(?,?)");$st->bind_param('ss',$name,$desc);$msg=$st->execute()?'Category added.':'Unable to add category (the name may already exist).';$st->close();}}
$rows=$conn->query("SELECT c.category_id,c.name,c.description,COUNT(p.package_id) package_count FROM categories c LEFT JOIN packages p ON p.category_id=c.category_id GROUP BY c.category_id,c.name,c.description ORDER BY c.category_id DESC");
?>
<section class="page-heading"><div><h1>Event Categories</h1><p>Manage event types such as wedding, birthday and corporate events.</p></div></section>
<?php if($msg):?><div class="notice"><?=h($msg)?></div><?php endif;?>
<section class="panel" style="margin-bottom:18px"><div class="panel-heading"><h3>Add New Category</h3></div><form method="post" class="grid-form"><div><label>Category name</label><input class="form-control" name="name" required></div><div><label>Description</label><input class="form-control" name="description"></div><div><button class="btn">Add Category</button></div></form></section>
<section class="panel"><div class="table-scroll"><table id="dataTable"><thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Packages</th></tr></thead><tbody><?php if($rows&&$rows->num_rows):while($r=$rows->fetch_assoc()):?><tr><td><?=$r['category_id']?></td><td><?=h($r['name'])?></td><td><?=h($r['description'])?></td><td><?=$r['package_count']?></td></tr><?php endwhile;else:?><tr><td colspan="4" class="empty-row">No categories found.</td></tr><?php endif;?></tbody></table></div></section>
<?php require __DIR__.'/layout-end.php'; ?>