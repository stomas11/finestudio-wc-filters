#requires -Version 5.1
[CmdletBinding()]
param(
	[string] $PluginRoot = ''
)

$ErrorActionPreference = 'Stop'
if ( '' -eq $PluginRoot ) {
	$ScriptRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
	$PluginRoot = ( Resolve-Path ( Join-Path $ScriptRoot '..' ) ).Path
}

$TextDomain = 'finestudio-wc-filters'
$LanguagesDir = Join-Path $PluginRoot 'languages'
$Utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[Console]::OutputEncoding = $Utf8NoBom

function T {
	param( [string] $Text )
	return [System.Net.WebUtility]::HtmlDecode( $Text )
}

function Write-Utf8NoBomFile {
	param(
		[string] $Path,
		[string] $Content
	)

	$FullPath = [System.IO.Path]::GetFullPath( $Path )
	$Directory = [System.IO.Path]::GetDirectoryName( $FullPath )
	if ( $Directory -and -not ( Test-Path $Directory ) ) {
		New-Item -ItemType Directory -Path $Directory | Out-Null
	}

	[System.IO.File]::WriteAllText( $FullPath, $Content, $script:Utf8NoBom )
}

function ConvertTo-PoString {
	param( [string] $Text )

	$Escaped = $Text.
		Replace( '\', '\\' ).
		Replace( '"', '\"' ).
		Replace( "`r", '' ).
		Replace( "`n", '\n' ).
		Replace( "`t", '\t' )

	return '"' + $Escaped + '"'
}

function ConvertTo-PhpString {
	param( [string] $Text )

	$Escaped = $Text.
		Replace( '\', '\\' ).
		Replace( "'", "\'" ).
		Replace( "`r", '' ).
		Replace( "`n", '\n' )

	return "'" + $Escaped + "'"
}

function Get-HeaderLines {
	param(
		[string] $Locale,
		[string] $LanguageTeam
	)

	$RevisionDate = Get-Date -Format 'yyyy-MM-dd HH:mmzzz'
	return @(
		'Project-Id-Version: Finestudio WC Filters',
		'Report-Msgid-Bugs-To: ',
		'POT-Creation-Date: 2026-05-04 09:59+0000',
		"PO-Revision-Date: $RevisionDate",
		'Last-Translator: Fine Studio',
		"Language-Team: $LanguageTeam",
		"Language: $Locale",
		'Plural-Forms: nplurals=3; plural=( n == 1 ) ? 0 : ( n >= 2 && n <= 4 ) ? 1 : 2;',
		'MIME-Version: 1.0',
		'Content-Type: text/plain; charset=UTF-8',
		'Content-Transfer-Encoding: 8bit',
		'X-Generator: tools/update-translations.ps1',
		"X-Domain: $script:TextDomain"
	)
}

$MessageIds = @(
	'Any',
	'Attribute Colors',
	'Auto submit on change',
	'Auto-generated WooCommerce product filters with per-category context and admin controls.',
	'Automatic',
	'Availability',
	'Button only',
	'Category Overrides',
	'Checkbox',
	'Close',
	'Collapse to first filters + Show all button',
	'Color attributes (taxonomy keys, comma separated)',
	'Columns count on desktop',
	'Columns on desktop',
	'Default',
	'Enable AJAX filtering',
	'Enabled',
	'Filter',
	'Filter Overview',
	'Filters',
	'Filters layout',
	'Fine Studio',
	'Finestudio WC Filters',
	'Hidden filter keys (comma separated)',
	'In stock only',
	'Label',
	'Max',
	'Min',
	'Multi-select',
	'No color attributes configured. Set them in Settings -> Color attributes (taxonomy keys, comma separated).',
	'On mobile show only "Filter" button and open filters panel on click',
	'On sale',
	'On sale only',
	'Order',
	'Price',
	'Price range',
	'Primary color',
	'Product Filters',
	'Products container ID (without #)',
	'Products container selector',
	'Radio',
	'Reset',
	'Save colors',
	'Save filters',
	'Save settings',
	'Select dropdown',
	'Settings',
	'Show all filters',
	'Show filters in sidebar panel (desktop + mobile)',
	'Show more options',
	'Show results',
	'Showing %d results',
	'Showing 1 result',
	'Stacked',
	'Submit mode',
	'Swatches',
	'Type',
	'Update browser URL',
	'Visible filters before "Show all"'
)

$Translations = @{
	'sk_SK' = [ordered] @{
		'Any' = (T '&#x013D;ubovo&#x013E;n&#x00E9;')
		'Attribute Colors' = (T 'Farby atrib&#x00FA;tov')
		'Auto submit on change' = (T 'Automaticky odosla&#x0165; pri zmene')
		'Auto-generated WooCommerce product filters with per-category context and admin controls.' = (T 'Automaticky generovan&#x00E9; filtre produktov WooCommerce s kontextom pod&#x013E;a kateg&#x00F3;rie a nastaveniami v administr&#x00E1;cii.')
		'Automatic' = 'Automaticky'
		'Availability' = (T 'Dostupnos&#x0165;')
		'Button only' = (T 'Len tla&#x010D;idlo')
		'Category Overrides' = (T 'Nastavenia pre kateg&#x00F3;rie')
		'Checkbox' = (T 'Za&#x0161;krt&#x00E1;vacie pol&#x00ED;&#x010D;ko')
		'Close' = (T 'Zavrie&#x0165;')
		'Collapse to first filters + Show all button' = (T 'Zbali&#x0165; na prv&#x00E9; filtre + tla&#x010D;idlo "Zobrazi&#x0165; v&#x0161;etko"')
		'Color attributes (taxonomy keys, comma separated)' = (T 'Farebn&#x00E9; atrib&#x00FA;ty (k&#x013E;&#x00FA;&#x010D;e taxon&#x00F3;mi&#x00ED; oddelen&#x00E9; &#x010D;iarkou)')
		'Columns count on desktop' = (T 'Po&#x010D;et st&#x013A;pcov na desktope')
		'Columns on desktop' = (T 'St&#x013A;pce na desktope')
		'Default' = (T 'Predvolen&#x00E9;')
		'Enable AJAX filtering' = (T 'Povoli&#x0165; AJAX filtrovanie')
		'Enabled' = (T 'Povolen&#x00E9;')
		'Filter' = 'Filter'
		'Filter Overview' = (T 'Preh&#x013E;ad filtrov')
		'Filters' = 'Filtre'
		'Filters layout' = (T 'Rozlo&#x017E;enie filtrov')
		'Fine Studio' = 'Fine Studio'
		'Finestudio WC Filters' = 'Finestudio WC Filters'
		'Hidden filter keys (comma separated)' = (T 'Skryt&#x00E9; k&#x013E;&#x00FA;&#x010D;e filtrov (oddelen&#x00E9; &#x010D;iarkou)')
		'In stock only' = 'Iba skladom'
		'Label' = (T 'N&#x00E1;zov')
		'Max' = 'Max'
		'Min' = 'Min'
		'Multi-select' = (T 'Viacn&#x00E1;sobn&#x00FD; v&#x00FD;ber')
		'No color attributes configured. Set them in Settings -> Color attributes (taxonomy keys, comma separated).' = (T 'Nie s&#x00FA; nastaven&#x00E9; &#x017E;iadne farebn&#x00E9; atrib&#x00FA;ty. Nastav&#x00ED;te ich v &#x010D;asti Nastavenia -> Farebn&#x00E9; atrib&#x00FA;ty (k&#x013E;&#x00FA;&#x010D;e taxon&#x00F3;mi&#x00ED; oddelen&#x00E9; &#x010D;iarkou).')
		'On mobile show only "Filter" button and open filters panel on click' = (T 'Na mobile zobrazi&#x0165; iba tla&#x010D;idlo "Filter" a po kliknut&#x00ED; otvori&#x0165; panel filtrov')
		'On sale' = 'V akcii'
		'On sale only' = 'Iba v akcii'
		'Order' = 'Poradie'
		'Price' = 'Cena'
		'Price range' = (T 'Cenov&#x00FD; rozsah')
		'Primary color' = (T 'Prim&#x00E1;rna farba')
		'Product Filters' = 'Filtre produktov'
		'Products container ID (without #)' = 'ID kontajnera produktov (bez #)'
		'Products container selector' = 'Selektor kontajnera produktov'
		'Radio' = (T 'Prep&#x00ED;na&#x010D;')
		'Reset' = (T 'Resetova&#x0165;')
		'Save colors' = (T 'Ulo&#x017E;i&#x0165; farby')
		'Save filters' = (T 'Ulo&#x017E;i&#x0165; filtre')
		'Save settings' = (T 'Ulo&#x017E;i&#x0165; nastavenia')
		'Select dropdown' = (T 'Rozba&#x013E;ovac&#x00ED; zoznam')
		'Settings' = 'Nastavenia'
		'Show all filters' = (T 'Zobrazi&#x0165; v&#x0161;etky filtre')
		'Show filters in sidebar panel (desktop + mobile)' = (T 'Zobrazi&#x0165; filtre v bo&#x010D;nom paneli (desktop + mobil)')
		'Show more options' = (T 'Zobrazi&#x0165; viac mo&#x017E;nost&#x00ED;')
		'Show results' = (T 'Zobrazi&#x0165; v&#x00FD;sledky')
		'Showing %d results' = (T 'Po&#x010D;et v&#x00FD;sledkov: %d')
		'Showing 1 result' = (T 'Po&#x010D;et v&#x00FD;sledkov: 1')
		'Stacked' = 'Pod sebou'
		'Submit mode' = (T 'Re&#x017E;im odosielania')
		'Swatches' = 'Vzorky'
		'Type' = 'Typ'
		'Update browser URL' = (T 'Aktualizova&#x0165; URL v prehliada&#x010D;i')
		'Visible filters before "Show all"' = (T 'Po&#x010D;et filtrov pred tla&#x010D;idlom "Zobrazi&#x0165; v&#x0161;etko"')
	}
	'cs_CZ' = [ordered] @{
		'Any' = (T 'Libovoln&#x00E9;')
		'Attribute Colors' = (T 'Barvy atribut&#x016F;')
		'Auto submit on change' = (T 'Automaticky odeslat p&#x0159;i zm&#x011B;n&#x011B;')
		'Auto-generated WooCommerce product filters with per-category context and admin controls.' = (T 'Automaticky generovan&#x00E9; filtry produkt&#x016F; WooCommerce s kontextem podle kategorie a nastaven&#x00ED;m v administraci.')
		'Automatic' = 'Automaticky'
		'Availability' = 'Dostupnost'
		'Button only' = (T 'Pouze tla&#x010D;&#x00ED;tko')
		'Category Overrides' = (T 'Nastaven&#x00ED; pro kategorie')
		'Checkbox' = (T 'Za&#x0161;krt&#x00E1;vac&#x00ED; pol&#x00ED;&#x010D;ko')
		'Close' = (T 'Zav&#x0159;&#x00ED;t')
		'Collapse to first filters + Show all button' = (T 'Sbalit na prvn&#x00ED; filtry + tla&#x010D;&#x00ED;tko "Zobrazit v&#x0161;e"')
		'Color attributes (taxonomy keys, comma separated)' = (T 'Barevn&#x00E9; atributy (kl&#x00ED;&#x010D;e taxonomi&#x00ED; odd&#x011B;len&#x00E9; &#x010D;&#x00E1;rkou)')
		'Columns count on desktop' = (T 'Po&#x010D;et sloupc&#x016F; na desktopu')
		'Columns on desktop' = 'Sloupce na desktopu'
		'Default' = (T 'V&#x00FD;choz&#x00ED;')
		'Enable AJAX filtering' = (T 'Povolit AJAX filtrov&#x00E1;n&#x00ED;')
		'Enabled' = 'Povoleno'
		'Filter' = 'Filtr'
		'Filter Overview' = (T 'P&#x0159;ehled filtr&#x016F;')
		'Filters' = 'Filtry'
		'Filters layout' = (T 'Rozlo&#x017E;en&#x00ED; filtr&#x016F;')
		'Fine Studio' = 'Fine Studio'
		'Finestudio WC Filters' = 'Finestudio WC Filters'
		'Hidden filter keys (comma separated)' = (T 'Skryt&#x00E9; kl&#x00ED;&#x010D;e filtr&#x016F; (odd&#x011B;len&#x00E9; &#x010D;&#x00E1;rkou)')
		'In stock only' = 'Pouze skladem'
		'Label' = (T 'N&#x00E1;zev')
		'Max' = 'Max'
		'Min' = 'Min'
		'Multi-select' = (T 'V&#x00ED;cen&#x00E1;sobn&#x00FD; v&#x00FD;b&#x011B;r')
		'No color attributes configured. Set them in Settings -> Color attributes (taxonomy keys, comma separated).' = (T 'Nejsou nastaven&#x00E9; &#x017E;&#x00E1;dn&#x00E9; barevn&#x00E9; atributy. Nastav&#x00ED;te je v &#x010D;&#x00E1;sti Nastaven&#x00ED; -> Barevn&#x00E9; atributy (kl&#x00ED;&#x010D;e taxonomi&#x00ED; odd&#x011B;len&#x00E9; &#x010D;&#x00E1;rkou).')
		'On mobile show only "Filter" button and open filters panel on click' = (T 'Na mobilu zobrazit pouze tla&#x010D;&#x00ED;tko "Filtr" a po kliknut&#x00ED; otev&#x0159;&#x00ED;t panel filtr&#x016F;')
		'On sale' = 'V akci'
		'On sale only' = 'Pouze v akci'
		'Order' = (T 'Po&#x0159;ad&#x00ED;')
		'Price' = 'Cena'
		'Price range' = (T 'Cenov&#x00E9; rozp&#x011B;t&#x00ED;')
		'Primary color' = (T 'Prim&#x00E1;rn&#x00ED; barva')
		'Product Filters' = (T 'Filtry produkt&#x016F;')
		'Products container ID (without #)' = (T 'ID kontejneru produkt&#x016F; (bez #)')
		'Products container selector' = (T 'Selektor kontejneru produkt&#x016F;')
		'Radio' = (T 'P&#x0159;ep&#x00ED;na&#x010D;')
		'Reset' = 'Resetovat'
		'Save colors' = (T 'Ulo&#x017E;it barvy')
		'Save filters' = (T 'Ulo&#x017E;it filtry')
		'Save settings' = (T 'Ulo&#x017E;it nastaven&#x00ED;')
		'Select dropdown' = (T 'Rozbalovac&#x00ED; seznam')
		'Settings' = (T 'Nastaven&#x00ED;')
		'Show all filters' = (T 'Zobrazit v&#x0161;echny filtry')
		'Show filters in sidebar panel (desktop + mobile)' = (T 'Zobrazit filtry v postrann&#x00ED;m panelu (desktop + mobil)')
		'Show more options' = (T 'Zobrazit dal&#x0161;&#x00ED; mo&#x017E;nosti')
		'Show results' = (T 'Zobrazit v&#x00FD;sledky')
		'Showing %d results' = (T 'Po&#x010D;et v&#x00FD;sledk&#x016F;: %d')
		'Showing 1 result' = (T 'Po&#x010D;et v&#x00FD;sledk&#x016F;: 1')
		'Stacked' = 'Pod sebou'
		'Submit mode' = (T 'Re&#x017E;im odes&#x00ED;l&#x00E1;n&#x00ED;')
		'Swatches' = 'Vzorky'
		'Type' = 'Typ'
		'Update browser URL' = (T 'Aktualizovat URL v prohl&#x00ED;&#x017E;e&#x010D;i')
		'Visible filters before "Show all"' = (T 'Po&#x010D;et filtr&#x016F; p&#x0159;ed tla&#x010D;&#x00ED;tkem "Zobrazit v&#x0161;e"')
	}
}

function New-PoContent {
	param(
		[string] $Locale,
		[string] $LanguageTeam,
		[System.Collections.IDictionary] $Messages
	)

	$Lines = New-Object System.Collections.Generic.List[string]
	$Lines.Add( 'msgid ""' )
	$Lines.Add( 'msgstr ""' )
	foreach ( $HeaderLine in ( Get-HeaderLines -Locale $Locale -LanguageTeam $LanguageTeam ) ) {
		$Lines.Add( ( ConvertTo-PoString ( $HeaderLine + "`n" ) ) )
	}
	$Lines.Add( '' )

	foreach ( $MsgId in $script:MessageIds ) {
		if ( -not $Messages.Contains( $MsgId ) ) {
			throw "Missing $Locale translation for: $MsgId"
		}

		$Lines.Add( 'msgid ' + ( ConvertTo-PoString $MsgId ) )
		$Lines.Add( 'msgstr ' + ( ConvertTo-PoString $Messages[$MsgId] ) )
		$Lines.Add( '' )
	}

	return ( $Lines -join "`n" )
}

function New-L10nPhpContent {
	param(
		[string] $Locale,
		[string] $LanguageTeam,
		[System.Collections.IDictionary] $Messages
	)

	$Header = Get-HeaderLines -Locale $Locale -LanguageTeam $LanguageTeam
	$HeaderMap = [ordered] @{
		'project-id-version' = 'Finestudio WC Filters'
		'report-msgid-bugs-to' = ''
		'pot-creation-date' = '2026-05-04 09:59+0000'
		'po-revision-date' = ( $Header | Where-Object { $_ -like 'PO-Revision-Date:*' } ).Substring( 18 )
		'last-translator' = 'Fine Studio'
		'language-team' = $LanguageTeam
		'language' = $Locale
		'plural-forms' = 'nplurals=3; plural=( n == 1 ) ? 0 : ( n >= 2 && n <= 4 ) ? 1 : 2;'
		'mime-version' = '1.0'
		'content-type' = 'text/plain; charset=UTF-8'
		'content-transfer-encoding' = '8bit'
		'x-generator' = 'tools/update-translations.ps1'
		'x-domain' = $script:TextDomain
	}

	$Lines = New-Object System.Collections.Generic.List[string]
	$Lines.Add( '<?php' )
	$Lines.Add( 'return array(' )
	foreach ( $Key in $HeaderMap.Keys ) {
		$Lines.Add( "`t" + ( ConvertTo-PhpString $Key ) + ' => ' + ( ConvertTo-PhpString $HeaderMap[$Key] ) + ',' )
	}
	$Lines.Add( "`t'messages' => array(" )
	foreach ( $MsgId in $script:MessageIds ) {
		$Lines.Add( "`t`t" + ( ConvertTo-PhpString $MsgId ) + ' => ' + ( ConvertTo-PhpString $Messages[$MsgId] ) + ',' )
	}
	$Lines.Add( "`t)," )
	$Lines.Add( ');' )

	return ( $Lines -join "`n" ) + "`n"
}

function New-MoFile {
	param(
		[string] $Path,
		[string] $Locale,
		[string] $LanguageTeam,
		[System.Collections.IDictionary] $Messages
	)

	$Entries = @{}
	$Entries[''] = ( ( Get-HeaderLines -Locale $Locale -LanguageTeam $LanguageTeam | ForEach-Object { $_ + "`n" } ) -join '' )
	foreach ( $MsgId in $script:MessageIds ) {
		$Entries[$MsgId] = $Messages[$MsgId]
	}

	$Items = @( $Entries.GetEnumerator() | Sort-Object -Property Key )
	$OriginalBytes = @()
	$TranslatedBytes = @()
	foreach ( $Item in $Items ) {
		$OriginalBytes += ,$script:Utf8NoBom.GetBytes( [string] $Item.Key )
		$TranslatedBytes += ,$script:Utf8NoBom.GetBytes( [string] $Item.Value )
	}

	$Count = $Items.Count
	$OriginalTableOffset = 28
	$TranslatedTableOffset = $OriginalTableOffset + ( $Count * 8 )
	$StringOffset = $TranslatedTableOffset + ( $Count * 8 )
	$OriginalOffsets = @()
	$TranslatedOffsets = @()
	$CurrentOffset = $StringOffset

	foreach ( $Bytes in $OriginalBytes ) {
		$OriginalOffsets += $CurrentOffset
		$CurrentOffset += $Bytes.Length + 1
	}

	foreach ( $Bytes in $TranslatedBytes ) {
		$TranslatedOffsets += $CurrentOffset
		$CurrentOffset += $Bytes.Length + 1
	}

	$Stream = New-Object System.IO.MemoryStream
	$Writer = New-Object System.IO.BinaryWriter( $Stream )
	$Writer.Write( [uint32] 2500072158 )
	$Writer.Write( [uint32] 0 )
	$Writer.Write( [uint32] $Count )
	$Writer.Write( [uint32] $OriginalTableOffset )
	$Writer.Write( [uint32] $TranslatedTableOffset )
	$Writer.Write( [uint32] 0 )
	$Writer.Write( [uint32] 0 )

	for ( $Index = 0; $Index -lt $Count; $Index++ ) {
		$Writer.Write( [uint32] $OriginalBytes[$Index].Length )
		$Writer.Write( [uint32] $OriginalOffsets[$Index] )
	}

	for ( $Index = 0; $Index -lt $Count; $Index++ ) {
		$Writer.Write( [uint32] $TranslatedBytes[$Index].Length )
		$Writer.Write( [uint32] $TranslatedOffsets[$Index] )
	}

	foreach ( $Bytes in $OriginalBytes ) {
		$Writer.Write( [byte[]] $Bytes )
		$Writer.Write( [byte] 0 )
	}

	foreach ( $Bytes in $TranslatedBytes ) {
		$Writer.Write( [byte[]] $Bytes )
		$Writer.Write( [byte] 0 )
	}

	$Writer.Flush()
	[System.IO.File]::WriteAllBytes( [System.IO.Path]::GetFullPath( $Path ), $Stream.ToArray() )
	$Writer.Dispose()
	$Stream.Dispose()
}

$LanguageTeams = @{
	'sk_SK' = 'Slovak'
	'cs_CZ' = 'Czech'
}

foreach ( $Locale in @( 'sk_SK', 'cs_CZ' ) ) {
	$Messages = $Translations[$Locale]
	$LanguageTeam = $LanguageTeams[$Locale]
	$BaseName = "$TextDomain-$Locale"

	Write-Utf8NoBomFile -Path ( Join-Path $LanguagesDir "$BaseName.po" ) -Content ( New-PoContent -Locale $Locale -LanguageTeam $LanguageTeam -Messages $Messages )
	Write-Utf8NoBomFile -Path ( Join-Path $LanguagesDir "$BaseName.l10n.php" ) -Content ( New-L10nPhpContent -Locale $Locale -LanguageTeam $LanguageTeam -Messages $Messages )
	New-MoFile -Path ( Join-Path $LanguagesDir "$BaseName.mo" ) -Locale $Locale -LanguageTeam $LanguageTeam -Messages $Messages

	Write-Host "Updated $BaseName.po, $BaseName.mo and $BaseName.l10n.php"
}
