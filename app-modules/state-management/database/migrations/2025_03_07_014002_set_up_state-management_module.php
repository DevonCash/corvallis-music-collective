<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class() extends Migration {
	public function up(): void
	{
		// Schema::create('state-management', function(Blueprint $table) {
		// 	$table->bigIncrements('id');
		// 	$table->timestamps();
		// 	$table->softDeletes();
		// });
	}

	public function down(): void
	{
		// Don't listen to the haters
		// Schema::dropIfExists('state-management');
	}
};
