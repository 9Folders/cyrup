<?php

    defined("INCLUDE_DIR") || exit("Not for direct run");

    DEBUG( D_INCLUDE, "pgsql.inc.php" );

    $GLOBALS['sql_last_result'] = null;
    $GLOBALS['dbconn'] = "";

    function sql_die( $message ) {
        print "<font color=red><b>FATAL: </b></font>";
        DEBUG( D_SQL_ERROR, $message );
        print pg_last_error(); //mysql_error()
        exit();
    }

    function sql_escape($str) {
       return pg_escape_literal($str);
    }

    function sql_connect( $database=SQL_DB, $username=SQL_USER, $password=SQL_PASS ) {
        DEBUG( D_FUNCTION, "sql_connect('$database', '$username', '\$password')" );
        @pg_connect("host=127.0.0.1 dbname=$database user=$username password=$password" ) or sql_die( "pg_connect(): Couldn't connect to the database" );
    }

    function sql_query( $query ) {
        DEBUG( D_FUNCTION, "sql_query('$query')" );
	($GLOBALS['sql_last_result'] = @pg_query($query)) or sql_die( "sql_query(): Unable to execute query: ".$query );
        return $GLOBALS['sql_last_result'];
    }

    function sql_num_rows() {
        DEBUG( D_FUNCTION, "sql_num_rows(...)" );
        $result = func_num_args() ? func_get_arg(0) : $GLOBALS['sql_last_result'];
        return pg_num_rows( $result );
    }

    function sql_affected_rows() {
        DEBUG( D_FUNCTION, "sql_affected_rows()" );
        $result = func_num_args() ? func_get_arg(0) : $GLOBALS['sql_last_result'];
        return pg_affected_rows($result);
    }

    function sql_insert_id( $tablename , $fieldname ) {
        DEBUG( D_FUNCTION, "sql_insert_id()" );
	$seq_name = "${tablename}_${fieldname}_seq";
	$cval = pg_fetch_row( pg_query( "SELECT currval('".$seq_name."')" ) );
        return $cval[0];
    }

    function sql_fetch_array() {
        DEBUG( D_FUNCTION, "sql_fetch_array(...)" );
        $result = func_num_args() ? func_get_arg(0) : $GLOBALS['sql_last_result'];
        return pg_fetch_array($result);
    }

    function sql_fetch_variable() {
        DEBUG( D_FUNCTION, "sql_fetch_variable(...)" );
        $result = func_num_args() ? func_get_arg(0) : $GLOBALS['sql_last_result'];
        $arr = sql_fetch_array( $result );
        if ( $arr == false || !is_array($arr) ) return false;
        return $arr[0];
    }

    function sql_fetch_row() {
        DEBUG( D_FUNCTION, "sql_fetch_row(...)" );
        $result = func_num_args() ? func_get_arg(0) : $GLOBALS['sql_last_result'];
        return pg_fetch_row( $result ); 
    }

     // Can be used for caching. Usage: sql_export( $query, $filename );
    function sql_export( $query, $filename ) {

        DEBUG( D_FUNCTION, "sql_export('$query','$filename')" );

        $S_NEWLINE = "\n";   // New line
        $S_COMMENT = '#';    // Comment sign
        $S_DELIMITER = "\t"; // Fields delimiter


        if ( ( $fh = fopen( $filename, "w" ) ) == FALSE ) sql_die( "sql_export(): Permission denied" );

        $result = sql_query( $query );
        $f_count = pg_num_fields( $result );
	fwrite( $fh, $S_COMMENT." " ); // Comment sign
        for ( $i = 0; $i < $f_count; $i++ ) fwrite( $fh, pg_field_name($result, $i). $S_DELIMITER);
        fwrite( $fh, $S_NEWLINE );

        while ( $row = sql_fetch_array( $result ) ) {
             for ( $i = 0; $i < $f_count; $i++ ) {
                 fwrite( $fh, str_replace(
                     [ "\\",   $S_COMMENT,      $S_NEWLINE, $S_DELIMITER ],
                     [ "\\\\", "\\".$S_COMMENT, "\\n",      "\\t" ],
                     $row[$i] )
                     .$S_DELIMITER );
             }
             fwrite( $fh, $S_NEWLINE );
         }

         fclose( $fh );
         return pg_fetch_array($result);
    }

    function sql_free_result() {
        DEBUG( D_FUNCTION, "sql_free_result(...)" );
        $result = func_num_args() ? func_get_arg(0) : $GLOBALS['sql_last_result'];
        return @pg_free_result($result) or sql_die( "pg_free_result(): Couldn't free result" );
    }

    function sql_close() {
        DEBUG( D_FUNCTION, "sql_close()" );
        pg_close();
    }

    sql_connect();
