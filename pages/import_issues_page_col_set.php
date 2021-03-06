<?php
	# Mantis - a php based bugtracking system
	require_once( 'core.php' );
	access_ensure_project_level( plugin_config_get( 'import_issues_threshold' ) );

	layout_page_header( plugin_lang_get( 'manage_issues' ) );
	layout_page_begin( __FILE__ );

	$import_it = plugin_page('import_issues');
	?>
<br />
<?php
	require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'import_issues_inc.php' );
	# Look if the import file name is in the posted data
	$f_import_file = gpc_get_file( 'import_file', -1 );
    $t_project_id = helper_get_current_project();

	# Check fields are set
	if ( is_blank( $f_import_file['tmp_name'] ) || ( $f_import_file['size'] == 0 ) ) {
		plugin_error( 'ERROR_FILE_UPLOAD', ERROR );
	}

	# File analysis
	$t_file_content = read_csv_file( $f_import_file['tmp_name'] );
	$t_separator = gpc_get_string('edt_cell_separator');
	$t_trim_columns = gpc_get_bool( 'cb_trim_blank_cols' );
	$t_trim_rows = gpc_get_bool( 'cb_skip_blank_lines' );
	$t_skip_first = gpc_get_bool( 'cb_skip_first_line' );
	$t_create_unknown_cats = gpc_get_bool( 'cb_create_unknown_cats' );

	$t_column_count = -1;
	$t_column_title = array();
	if( count( $t_file_content ) <= 0 ) {
		error_parameters( plugin_lang_get( 'error_nolines' ) );
		plugin_error( 'ERROR_FILE_FORMAT', ERROR );
	}

	foreach( $t_file_content as $t_key => &$t_file_line ) {
		$t_elements = read_csv_row( $t_file_line, $t_separator );

		# First line
		if( $t_column_count < 0 ) {
			#  If 0 or 1 column
			if( count( $t_elements ) <= 1 ) {
				error_parameters( sprintf( plugin_lang_get( 'error_noseparator' ), $t_separator) );
				plugin_error( 'ERROR_FILE_FORMAT', ERROR );
			}
			elseif (
				$t_trim_rows && (trim(implode(' ' , $t_elements)) == '')
			) {
				if( $t_skip_first ) {
					error_parameters( plugin_lang_get('error_empty_header' ) );
					plugin_error( 'ERROR_FILE_FORMAT', ERROR );
				}
				else {
					$t_file_line = null;
				}
			}

			if( $t_trim_columns ) {
				for( $i = 0 ; $i < count($t_elements) ; $i++ ) {
					if( trim($t_elements[$i]) == '' ) {
						$t_elements = array_slice( $t_elements , 0 , $i );
						break 1;
					}
				}
			}
			$t_column_count = count( $t_elements );
			$t_column_title = $t_elements;
		}

		# Other lines
		elseif( $t_column_count != count( $t_elements ) ) {
			if( $t_trim_columns ) { # @@@ u.sommer added
				$t_row = explode( $t_separator , $t_file_line );
				$t_row = array_slice( $t_row , 0 , $t_column_count );
				$t_file_line = implode( $t_separator , $t_row );
			}
			else {
				error_parameters( sprintf( plugin_lang_get( 'error_col_count' ), $t_separator) );
				plugin_error( 'ERROR_FILE_FORMAT', ERROR );
			}
		}

		if (
			$t_trim_rows && trim(implode(' ' , $t_elements)) == ''
		) {
			unset( $t_file_content[$t_key] );
			$t_file_content = array_merge($t_file_content);
		}
	}

	if( is_writable( $f_import_file['tmp_name'] ) ) {
		if( $handle = fopen( $f_import_file['tmp_name'], "wb" ) ) {
			foreach( $t_file_content as &$t_file_line ) {
				$t_written = fwrite( $handle , $t_file_line . "\n" );
			}
			fclose( $handle );
		}
		else {
			error_parameters( plugin_lang_get( 'error_file_not_opened' ) );
			plugin_error( 'ERROR_FILE_FORMAT', ERROR );
		}
	}
	else {
		error_parameters( plugin_lang_get( 'error_file_not_writable' ) );
		plugin_error( 'ERROR_FILE_FORMAT', ERROR );
	}

	# Move file
	$t_file_name = tempnam( dirname($f_import_file['tmp_name']), 'tmp' );
	move_uploaded_file( $f_import_file['tmp_name'], $t_file_name );
?>

<!-- Set fields form -->
<div class="">
   <div class="space-10"></div>
   <div id="config-div" class="form-container">
     <div class="widget-box widget-color-blue2">
        <div class="widget-header widget-header-small">
           <h4 class="widget-title lighter">
              <?php echo $f_import_file['name'] ?>
           </h4>
        </div>
        <div class="widget-body">
           <div class="widget-main no-padding">
              <div class="form-container">
                 <div class="table-responsive">
                    <table class="table table-bordered table-condensed table-hover table-striped">
                       <fieldset>
						   <tr class="row-category">
	<?php
	# Write columns labels
	for( $i = 0; $i < $t_column_count; $i++ ) {
		echo '<td>';
		if( !$t_skip_first ) {
			echo sprintf( plugin_lang_get( 'column_number' ), $i + 1);
		}
		else {
			echo prepare_output($t_column_title[$i]);
		}
		echo '</td>';
	}
	?>
	</tr>
<?php
	# Display first file lines
	$t_first_run = true;
	$t_display_max = 3;

	foreach( $t_file_content as &$t_file_line ) {
		# Ignore columns labels
		if( $t_first_run && $t_skip_first ) {
			$t_first_run = false;
			continue;
		}

		echo '<tr>';

		# Still more lines (add "...")
		if( --$t_display_max < 0 ) {
			echo str_repeat('<td>&hellip;</td>', $t_column_count);
			echo '</tr>';
			break;
		}
		else {
			# Write values
			foreach( read_csv_row( $t_file_line, $t_separator ) as $t_element ) {
				echo '<td>' . prepare_output($t_element) . '</td>';
			}
		}
		echo '</tr>';
	}
?>
</fieldset>
                    </table>
                 </div>
              </div>
           </div>
        </div>
     </div>
   </div>
   <div class="space-10"></div>
</div>

<br />

<!-- Set fields form -->
<div class="col-xs-12 col-md-8 col-md-offset-2">
   <div class="space-10"></div>
   <div id="config-div" class="form-container">
      <form method="post" enctype="multipart/form-data" action="<?php echo $import_it ?>">
         <div class="widget-box widget-color-blue2">
            <div class="widget-header widget-header-small">
               <h4 class="widget-title lighter">
                  Column Mapping
               </h4>
            </div>
            <div class="widget-body">
               <div class="widget-main no-padding">
                  <div class="form-container">
                     <div class="table-responsive">
                        <table class="table table-bordered table-condensed table-striped">
                           <fieldset>
                           	   <tr>
                           	   	   <td>
                           	   	       <?php echo plugin_lang_get( 'issues_columns' ) ?>
                           	   	   </td>
                           	   	   <td>
                           	   	   	   Matching Action
                           	   	   </td>
                           	   	   <td>
                           	   	       Primary Key
                           	   	   </td>
                           	   </tr>
<?php
	$t_column_title = array_map( 'trim', $t_column_title );
	for( $t_fields = $g_all_fields, $i = 0; $i < $t_column_count; next( $t_fields ), $i++ ) {
		if ( is_blank( $t_column_title[$i] ) ) {
			continue;
		}
        $hasId = false;
		# Map imported columns to fields.
		if ( strtolower( $t_column_title[$i] ) == 'id' ) {
		    $hasId = true;
			# By default use import as new issues mode rather than update issues with matching ids.
			$t_found_field = false;
		} else if( isset( $g_all_fields[$t_column_title[$i]] ) ) {
			$t_found_field = $t_column_title[$i];
		} else if( isset( $g_all_fields[ str_replace( '_id', '', $t_column_title[$i] ) ] ) ) {
			$t_found_field = str_replace( '_id', '', $t_column_title[$i] );
		} else
		{
			$t_found_field = array_isearch( prepare_output($t_column_title[$i]), $g_all_fields );
		}
        $t_require = false;
        $t_related_custom_field_ids = custom_field_get_linked_ids( $t_project_id );

        if ($t_found_field == 'category' || $t_found_field == 'summary') {
            $t_require = true;
        } else {
            foreach ($t_related_custom_field_ids as $t_id) {
                $t_def = custom_field_get_definition($t_id);
                if ($hasId) {
                    if ($t_def['require_update'] && $t_def['name'] === $t_column_title[$i]) {
                        $t_require = $t_def['require_update'] ? true : false;
                        break;
                    }
                } else {
                    if ($t_def['require_report'] && $t_def['name'] === $t_column_title[$i]) {
                        $t_require = $t_def['require_report'] ? true : false;
                        break;
                    }
                }

            }
        }

		# Write
		?>
		<tr>
			<td class="category">
                <?php if( $t_require) {?><span class="required">*</span><?php } ?>
				<?php
					if( !$t_skip_first ) {
						echo sprintf( plugin_lang_get( 'column_number' ), $i + 1);
					}
					else {
						echo prepare_output($t_column_title[$i]);
					}
				?>
			</td>
			<td>
				<select name="columns[]">
					<?php print_all_fields_option_list( $t_found_field !== false ? $t_found_field : 'ignore_column' ) ?>
				</select>
			</td>
			<td>
				<input type="checkbox" name="cb_keys[]" value="<?php echo $i?>"/>
			</td>
		</tr><?php
	}
?>
			<input type="hidden" name="cb_skip_first_line" value="<?php echo $t_skip_first ?>" />
			<input type="hidden" name="cb_skip_blank_lines" value="<?php echo $t_trim_rows ?>" />
			<input type="hidden" name="cb_trim_blank_cols" value="<?php echo $t_trim_columns ?>" />
			<input type="hidden" name="edt_cell_separator" value="<?php echo $t_separator ?>" />
			<input type="hidden" name="cb_create_unknown_cats" value="<?php echo $t_create_unknown_cats ?>" />
			<input type="hidden" name="import_file" value="<?php echo $t_file_name ?>" />
</fieldset>
                        </table>
                     </div>
                  </div>
               </div>
               <div class="widget-toolbox padding-8 clearfix">
			      <input type="submit" class="btn btn-primary btn-white btn-round" id="importForm" value="<?php echo plugin_lang_get( 'file_button' ) ?>" onclick="return checkFields();" />
               </div>
            </div>
         </div>
      </form>
   </div>
   <div class="space-10"></div>
</div>

<script type="text/javascript">
function checkFields() {
	var allDropdowns = document.getElementsByName("columns[]");
	var dropdownArray = [];
	var dupeArray = [];
	var dupeCnt = 0;
	for(var x = 0; x < allDropdowns.length; x++) {
		var currentDropdown = allDropdowns[x].options[allDropdowns[x].options.selectedIndex];
		if (dropdownArray.indexOf(currentDropdown.value) < 0) {
			dropdownArray[x] = currentDropdown.value;
		} else if (currentDropdown.value != 'ignore_column') {
			dupeArray[dupeCnt] = currentDropdown.text;
			dupeCnt++;
		}
	}
	if (dupeArray.length > 0) {
		alert('<?php echo str_replace('\'', '\\\'', plugin_lang_get( 'error_col_select_multiple' )) ?>\r\n\r\n' + dupeArray.toString().replace(/,/g, "\r\n"));
		return false;
	} else {
		return true;
	}
}
</script>

<?php
layout_page_end();
