<% 
	for(var m in sf){ %>
		<span class="dtl_label_tag dtl_label_tag_tsk">
		<a class="only_set_activecls" href="<%= HTTP_ROOT %><?= __('dashboard#tasks/custom-') ?><%= sf[m]['SearchFilter']['id'] %>" id="ftopt_rt<%= sf[m]['SearchFilter']['id'] %>" data-val="<%= sf[m]['SearchFilter']['id'] %>" title="<%= sf[m]['SearchFilter']['name'] %>" data-valname="<%= sf[m]['SearchFilter']['url_name'] %>" onclick="setTabSelectionFilter(this);">
				<%= sf[m]['SearchFilter']['namewithcount'] %>
		</a>
		</span>
<% } %>
 <% if(!sf.length) { %>
	<span class="no-data-found"><?= __('No filter found.') ?></span>
<% } %>