<?php
include_once 'layout-start.php';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['title'])){
    $title=trim($_POST['title']);
    $cat=(int)$_POST['category_id'];
    $price=(float)$_POST['base_price'];
    $desc=trim($_POST['description']??'');
    if($title!==''&&$cat>0){
        $st=$conn->prepare("INSERT INTO packages(category_id,title,base_price,description,is_active)
        VALUES(?,?,?,?,1)");
        $st->bind_param('isds',$cat,$title,$price,$desc);
        $msg=$st->execute()?'Package added.':'Could not add package.';
        $st->close();
    }
}
$cats=$conn->query("SELECT category_id,name FROM categories ORDER BY name");
$rows=$conn->query("SELECT p.package_id,p.title,p.base_price,p.description,p.is_active,c.name 
category_name,COUNT(b.booking_id) booking_count FROM packages p LEFT JOIN categories c 
ON p.category_id=c.category_id LEFT JOIN bookings b ON b.package_id=p.package_id GROUP BY 
p.package_id,p.title,p.base_price,p.description,p.is_active,c.name ORDER BY p.package_id DESC");
?>
<section class="page-heading">
    <div>
        <h1>Decoration Packages</h1>
        <p>View packages and add new decoration offers.</p>
    </div>
</section>
<?php if($msg):?>
<div class="notice"><?=h($msg)?></div>
<?php endif;?>
<section class="panel" style="margin-bottom:18px">
    <div class="panel-heading">
        <h3>Add Package</h3>
    </div>
    <form method="post" class="grid-form">
        <div>
            <label>Package title</label>
            <input class="form-control" name="title" required>
        </div>
        <div>
            <label>Category</label>
            <select class="form-control" name="category_id" required>
                <option value="">Select category</option>
                <?php if($cats)while($c=$cats->fetch_assoc()):?>
                <option value="<?=$c['category_id']?>"><?=h($c['name'])?>
                </option>
                <?php endwhile;?>
            </select>
        </div>
        <div>
            <label>Base price (৳)</label>
            <input class="form-control" type="number" min="0" step=".01" name="base_price" required>
        </div>
        <div>
            <label>Description</label>
            <input class="form-control" name="description">
        </div>
        <div>
            <button class="btn">Add Package</button>
        </div>
    </form>
</section>
<section class="panel">
    <div class="table-scroll">
        <table id="dataTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Package</th>
                    <th>Category</th>
                    <th>Base Price</th>
                    <th>Bookings</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if($rows&&$rows->num_rows):while($r=$rows->fetch_assoc()):?>
                <tr>
                    <td><?=$r['package_id']?></td>
                    <td><b><?=h($r['title'])?></b><span class="subtext"><?=h($r['description'])?></span></td>
                    <td><?=h($r['category_name']??'—')?></td>
                    <td><?=money($r['base_price'])?></td>
                    <td><?=$r['booking_count']?></td>
                    <td>
                        <span class="status <?=$r['is_active']?'active':'inactive'?>">
                        <?=$r['is_active']?'Active':'Inactive'?>
                        </span>
                    </td>
                </tr>
                <?php endwhile;else:?>
                <tr>
                    <td colspan="6" class="empty-row">No packages found.</td>
                </tr><?php endif;?>
            </tbody>
        </table>
    </div>
</section>
<?php include 'layout-end.php'; ?>