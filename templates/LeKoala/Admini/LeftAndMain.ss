<!DOCTYPE html>
<html lang="$Locale.RFC1766">
	<head>
	<% base_tag %>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>$Title</title>

    <script
        src="https://cdn.jsdelivr.net/gh/lekoala/nomodule-browser-warning.js/nomodule-browser-warning.min.js"
        nomodule defer id="nomodule-browser-warning"></script>
</head>
<body class="admini<% if HasMinimenu %> minimenu<% end_if %>"
    data-ping="$SessionKeepAlivePing"
    data-frameworkpath="$ModulePath(silverstripe/framework)"
    data-member-tempid="$CurrentMember.TempIDHash.ATT">
    <%-- include LeKoala\Admini\ToastMessages --%>
	<div class="wrapper">
		$Menu
        $Tools
        <main class="main $BaseCSSClasses">
		$Content
        </main>
    </div>
    <script type="module">window.admini.init()</script>
</body>
</html>
