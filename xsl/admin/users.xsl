<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:include href="tpl.default.xsl" />

	<xsl:template name="tabs">
		<ul class="tabs">
			<xsl:if test="/root/meta/controller = 'users'">

				<xsl:call-template name="tab">
					<xsl:with-param name="href"      select="'users'" />
					<xsl:with-param name="text"      select="'List users'" />
				</xsl:call-template>

				<xsl:call-template name="tab">
					<xsl:with-param name="href"      select="'users/user'" />
					<xsl:with-param name="text"      select="'Add user'" />
					<xsl:with-param name="action"    select="'user'" />
					<xsl:with-param name="url_param" select="''" />
				</xsl:call-template>

			</xsl:if>
			<xsl:if test="/root/meta/controller = 'fields'">

				<xsl:call-template name="tab">
					<xsl:with-param name="href"      select="'fields'" />
					<xsl:with-param name="text"      select="'List fields'" />
				</xsl:call-template>

				<xsl:call-template name="tab">
					<xsl:with-param name="href"      select="'fields/add_field'" />
					<xsl:with-param name="action"    select="'add_field'" />
					<xsl:with-param name="text"      select="'Add field'" />
				</xsl:call-template>

			</xsl:if>
		</ul>
	</xsl:template>

	<xsl:template match="/">
		<xsl:if test="/root/content[../meta/controller = 'fields' and ../meta/action = 'index']">
			<xsl:call-template name="template">
				<xsl:with-param name="title" select="'Admin - Users'" />
				<xsl:with-param name="h1"    select="'Fields'" />
			</xsl:call-template>
		</xsl:if>
		<xsl:if test="/root/content[../meta/controller = 'fields' and ../meta/action = 'add_field']">
			<xsl:call-template name="template">
				<xsl:with-param name="title" select="'Admin - Users'" />
				<xsl:with-param name="h1"    select="'Add field'" />
			</xsl:call-template>
		</xsl:if>
		<xsl:if test="/root/content[../meta/controller = 'fields' and ../meta/action = 'edit_field']">
			<xsl:call-template name="template">
				<xsl:with-param name="title" select="'Admin - Users'" />
				<xsl:with-param name="h1"    select="'Edit field'" />
			</xsl:call-template>
		</xsl:if>
		<xsl:if test="/root/content[../meta/controller = 'users' and ../meta/action = 'index']">
			<xsl:call-template name="template">
				<xsl:with-param name="title" select="'Admin - Users'" />
				<xsl:with-param name="h1"    select="'Users'" />
			</xsl:call-template>
		</xsl:if>
		<xsl:if test="/root/content[../meta/controller = 'users' and ../meta/action = 'user' and ../meta/url_params/id]">
			<xsl:call-template name="template">
				<xsl:with-param name="title" select="'Admin - Users'" />
				<xsl:with-param name="h1"    select="'Edit user'" />
			</xsl:call-template>
		</xsl:if>
		<xsl:if test="/root/content[../meta/controller = 'users' and ../meta/action = 'user' and not(../meta/url_params/id)]">
			<xsl:call-template name="template">
				<xsl:with-param name="title" select="'Admin - Users'" />
				<xsl:with-param name="h1"    select="'Add user'" />
			</xsl:call-template>
		</xsl:if>
	</xsl:template>

	<!-- List fields -->
	<xsl:template match="content[../meta/controller = 'fields' and ../meta/action = 'index']">
		<table>
			<thead>
				<tr>
					<th class="small_row">Field ID</th>
					<th>Name</th>
					<th class="medium_row">Action</th>
				</tr>
			</thead>
			<tbody>
				<xsl:for-each select="users/field">
					<tr>
						<xsl:if test="position() mod 2 = 1">
							<xsl:attribute name="class">odd</xsl:attribute>
						</xsl:if>
						<td><xsl:value-of select="@id" /></td>
						<td><xsl:value-of select="." /></td>
						<td>
							[<a>
							<xsl:attribute name="href">
								<xsl:text>fields/edit_field/</xsl:text>
								<xsl:value-of select="@id" />
							</xsl:attribute>
							<xsl:text>Edit</xsl:text>
							</a>]
							[<a>
							<xsl:attribute name="href">
								<xsl:text>fields/rm_field/</xsl:text>
								<xsl:value-of select="@id" />
							</xsl:attribute>
							<xsl:text>Delete</xsl:text>
							</a>]
						</td>
					</tr>
				</xsl:for-each>
			</tbody>
		</table>
	</xsl:template>

	<!-- Add or edit field -->
	<xsl:template match="content[../meta/controller = 'fields' and (../meta/action = 'add_field' or ../meta/action = 'edit_field')]">
		<form method="post" action="fields/add_field">
			<xsl:if test="../meta/action = 'edit_field'">
				<xsl:attribute name="action">
					<xsl:text>fields/edit_field/</xsl:text>
					<xsl:value-of select="field/@id" />
				</xsl:attribute>
			</xsl:if>

			<!-- Include an error -->
			<xsl:if test="errors/form_errors/field_name">
				<xsl:if test="errors/form_errors/field_name = 'User::field_name_available'">
					<xsl:call-template name="form_line">
						<xsl:with-param name="id" select="'field_name'" />
						<xsl:with-param name="label" select="'Field name:'" />
						<xsl:with-param name="error" select="'This field name is already taken'" />
					</xsl:call-template>
				</xsl:if>

				<xsl:if test="errors/form_errors/field_name = 'Valid::not_empty'">
					<xsl:call-template name="form_line">
						<xsl:with-param name="id" select="'field_name'" />
						<xsl:with-param name="label" select="'Field name:'" />
						<xsl:with-param name="error" select="'Must not be empty'" />
					</xsl:call-template>
				</xsl:if>
			</xsl:if>

			<!-- no error -->
			<xsl:if test="not(errors/form_errors/field_name)">
				<xsl:call-template name="form_line">
					<xsl:with-param name="id" select="'field_name'" />
					<xsl:with-param name="label" select="'Field name:'" />
				</xsl:call-template>
			</xsl:if>

			<xsl:if test="../meta/action = 'edit_field'">
				<xsl:call-template name="form_button">
					<xsl:with-param name="value" select="'Save'" />
				</xsl:call-template>
			</xsl:if>
			<xsl:if test="../meta/action = 'add_field'">
				<xsl:call-template name="form_button">
					<xsl:with-param name="value" select="'Add'" />
				</xsl:call-template>
			</xsl:if>

		</form>
	</xsl:template>

	<!-- List users -->
	<xsl:template match="content[../meta/controller = 'users' and ../meta/action = 'index']">
		<table>
			<thead>
				<tr>
					<th class="small_row">User ID</th>
					<th>Username</th>
					<th>Role(s)</th>
					<th>Lastname</th>
					<th>Firstname</th>
					<th class="medium_row">Action</th>
				</tr>
			</thead>
			<tbody>
				<xsl:for-each select="users/user">
					<tr>
						<xsl:if test="position() mod 2 = 1">
							<xsl:attribute name="class">odd</xsl:attribute>
						</xsl:if>
						<td><xsl:value-of select="@id" /></td>
						<td><xsl:value-of select="username" /></td>
						<td><xsl:value-of select="role" /></td>
						<td><xsl:value-of select="lastname" /></td>
						<td><xsl:value-of select="firstname" /></td>
						<td>
							[<a>
							<xsl:attribute name="href">
								<xsl:text>users/user?id=</xsl:text>
								<xsl:value-of select="@id" />
							</xsl:attribute>
							<xsl:text>Edit</xsl:text>
							</a>]
							[<a>
							<xsl:attribute name="href">
								<xsl:text>users/rm_user/</xsl:text>
								<xsl:value-of select="@id" />
							</xsl:attribute>
							<xsl:text>Delete</xsl:text>
							</a>]
						</td>
					</tr>
				</xsl:for-each>
			</tbody>
		</table>
	</xsl:template>

	<!-- Add or edit user -->
	<xsl:template match="content[../meta/controller = 'users' and ../meta/action = 'user']">
		<form method="post" action="users/user" autocomplete="off">
			<xsl:if test="../meta/url_params/id">
				<xsl:attribute name="action">
					<xsl:text>users/user?id=</xsl:text>
					<xsl:value-of select="../meta/url_params/id" />
				</xsl:attribute>
			</xsl:if>

			<h2>Basic user information</h2>

			<!-- User ID -->
			<xsl:if test="../meta/action = 'edit_user'">
				<xsl:call-template name="form_line">
					<xsl:with-param name="type"  select="'none'" />
					<xsl:with-param name="label" select="'User ID:'" />
					<xsl:with-param name="value" select="user/@id" />
				</xsl:call-template>
			</xsl:if>

			<!-- Username -->
			<xsl:choose>
				<xsl:when test="errors/form_errors/username = 'Valid::not_empty'">
					<xsl:call-template name="form_line">
						<xsl:with-param name="id"    select="'username'" />
						<xsl:with-param name="label" select="'Username:'" />
						<xsl:with-param name="error" select="'A username is required'" />
					</xsl:call-template>
				</xsl:when>
				<xsl:when test="errors/form_errors/username = 'User::username_available'">
					<xsl:call-template name="form_line">
						<xsl:with-param name="id"    select="'username'" />
						<xsl:with-param name="label" select="'Username:'" />
						<xsl:with-param name="error" select="'This username already taken'" />
					</xsl:call-template>
				</xsl:when>
				<xsl:otherwise>
					<xsl:call-template name="form_line">
						<xsl:with-param name="id"    select="'username'" />
						<xsl:with-param name="label" select="'Username:'" />
					</xsl:call-template>
				</xsl:otherwise>
			</xsl:choose>

			<!-- Password -->
			<xsl:if test="errors/form_errors/password">
				<xsl:call-template name="form_line">
					<xsl:with-param name="id"    select="'password'" />
					<xsl:with-param name="label" select="'Password:'" />
					<xsl:with-param name="type"  select="'password'" />
					<xsl:with-param name="error" select="'A password is required'" />
				</xsl:call-template>
			</xsl:if>
			<xsl:if test="not(errors/form_errors/password)">
				<xsl:call-template name="form_line">
					<xsl:with-param name="id"    select="'password'" />
					<xsl:with-param name="label" select="'Password:'" />
					<xsl:with-param name="type"  select="'password'" />
				</xsl:call-template>
			</xsl:if>

			<xsl:if test="../meta/action = 'edit_user'">
				<p>(Leave password field blank to use the old password)</p>
			</xsl:if>

			<h2>User details</h2>

			<p>&quot;role: admin&quot; grants a user access to the admin interface</p>

			<xsl:for-each select="formdata/field">
				<xsl:if test="@id != 'username'">
					<xsl:call-template name="form_line">
						<xsl:with-param name="id"    select="@id" />
						<xsl:with-param name="label" select="/root/content/users/field[@id = substring-before(substring-after(current()/@id, '_'), '_')]" />
					</xsl:call-template>
				</xsl:if>
			</xsl:for-each>

			<p>(To remove a field, just leave it blank)</p>

			<h2>Add user detail field:</h2>

			<label for="add_field">
				<xsl:text>Add field:</xsl:text>
				<select name="add_field">
					<option selected="selected"></option>
					<xsl:for-each select="users/field">
						<option value="{@id}"><xsl:value-of select="." /></option>
					</xsl:for-each>
				</select>
			</label>
			<input type="submit" name="do_add_field" value="Add Field" class="button add_field" />

			<xsl:if test="../meta/url_params/id">
				<xsl:call-template name="form_button">
					<xsl:with-param name="value" select="'Save changes'" />
				</xsl:call-template>
			</xsl:if>
			<xsl:if test="not(../meta/url_params/id)">
				<xsl:call-template name="form_button">
					<xsl:with-param name="value" select="'Add User'" />
				</xsl:call-template>
			</xsl:if>

		</form>
	</xsl:template>

</xsl:stylesheet>
