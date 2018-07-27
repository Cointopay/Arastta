<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" onclick="save('save')" form="form-cointopay" data-toggle="tooltip" title="" class="btn btn-success" data-original-title="Save"><i class="fa fa-check"></i></button>
		<button type="submit" form="form-cointopay" data-toggle="tooltip" title="" class="btn btn-default" data-original-title="Save & Close"><i class="fa fa-save text-success"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-times-circle text-danger"></i></a></div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-cointopay" class="form-horizontal">
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-account"><?php echo $entry_account; ?></label>
            <div class="col-sm-10">
              <input type="text" name="cointopay_account" value="<?php echo $cointopay_account; ?>" placeholder="<?php echo $entry_account; ?>" id="input-account" class="form-control" />
              <?php if ($error_account) { ?>
              <div class="text-danger"><?php echo $error_account; ?></div>
              <?php } ?>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-secret"><span data-toggle="tooltip" title="The security code to confirm transactions with (must be the same as defined on the merchant account configuration page"><?php echo $entry_secret; ?></span></label>
            <div class="col-sm-10">
              <input type="text" name="cointopay_secret" value="<?php echo $cointopay_secret; ?>" placeholder="<?php echo $entry_secret; ?>" id="input-secret" class="form-control" />
              <?php if ($error_secret) { ?>
              <div class="text-danger"><?php echo $error_secret; ?></div>
              <?php } ?>
            </div>
          </div>

           <div class="form-group">
            <label class="col-sm-2 control-label" for="input-secret"><span data-toggle="tooltip" title="Your coinotpay API KEY">API Key</span></label>
            <div class="col-sm-10">
              <input type="text" name="cointopay_api_key" value="<?php echo $cointopay_api_key; ?>" placeholder="API KEY" id="input-api-key" class="form-control" />
            </div>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript"><!--
function save(type){
	var input = document.createElement('input');
	input.type = 'hidden';
	input.name = 'button';
	input.value = type;
	form = $("form[id^='form-']").append(input);
	form.submit();
}
//--></script>
            
<?php echo $footer; ?>