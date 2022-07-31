<?php $this->extend('content.tpl.php'); ?>

<?php $this->start('content'); ?>
<p><b>Formal w/ Tico</b> Index page</p>
<b>Data</b>
<pre><?php print_r($data); ?></pre><br />
<?php if (!empty($err)) { ?>
<b>Errors</b>
<pre><?php echo implode("\n", $err); ?></pre>
<?php } ?>
<br />
<form method="post">
Foo: <input name="foo" type="text" value="" /><br />
Nums: <input name="num[]" type="text" value="0.1" /><input name="num[]" type="text" value="1.2" /><br />
Dates: <input name="date[]" type="text" value="2012-11-02" /><input name="date[]" type="text" value="20-11-02" /><br />
<button type="submit">Submit</button>
</form>
<?php $this->end('content'); ?>
