# WooCommerce Product Filters Plugin – zadanie pre AI vývojára

## Cieľ pluginu

Vytvoriť vlastný WordPress plugin pre WooCommerce, ktorý automaticky generuje produktové filtre podľa dát v produktoch, kategóriách a atribútoch. Administrátor nemá ručne vytvárať jednotlivé filtre od nuly. Má mať jednoduché rozhranie, kde vie automaticky nájdené filtre zapnúť/vypnúť, skryť, zoradiť a nastaviť spôsob ich zobrazenia.

Plugin musí byť kompatibilný s:

- WooCommerce
- WPML
- Polylang
- WordPress štandardmi
- modernými témami a page buildermi, napríklad Elementor

Hlavná myšlienka pluginu:

Plugin automaticky zistí dostupné filtre podľa aktuálneho kontextu stránky.

- Na hlavnom archíve / shop stránke zobrazí všetky relevantné filtre naprieč e-shopom.
- Na stránke konkrétnej kategórie zobrazí len filtre odvodené z produktov v danej kategórii.
- Administrátor si vie určiť poradie filtrov.
- Administrátor vie niektoré filtre skryť globálne alebo pre konkrétnu kategóriu.
- Administrátor vie nastaviť typ zobrazenia filtra, napríklad checkboxy, select, rozsah alebo farebné štvorčeky.
- Pri atribúte typu farba musí byť možné nastaviť farby pre jednotlivé hodnoty.

Cieľ je, aby bol plugin čo najjednoduchší na používanie.

Administrátor nechce ručne vytvárať samostatné sady filtrov pre každú kategóriu. Plugin má filtre odvodiť automaticky z produktov a administrátor má iba upravovať ich správanie.

---

## Príklad použitia

E-shop má produkty v rôznych kategóriách.

Na hlavnej stránke obchodu alebo archive stránke sa zobrazia všetky dostupné filtre, napríklad:

- cena
- kategória
- značka
- farba
- veľkosť
- materiál
- dostupnosť

Ak zákazník otvorí kategóriu **Kozmetika**, plugin automaticky zistí, aké atribúty majú produkty v tejto kategórii, a zobrazí len relevantné filtre, napríklad:

- cena
- značka
- počet ihiel
- dĺžka ihiel
- typ použitia
- materiál

Ak sa atribút **Veľkosť** v kategórii Kozmetika vôbec nepoužíva, nemá sa tam zobrazovať.

Administrátor si následne vie v administrácii nastaviť, že:

- filter **Cena** bude vždy prvý
- filter **Farba** sa má zobrazovať ako farebné štvorčeky
- filter **Materiál** sa má zobrazovať ako checkboxy
- filter **Značka** sa má skryť v kategórii Kozmetika
- filter **Počet ihiel** sa má v kategórii Kozmetika zobraziť pred filtrom Dĺžka

---

## Hlavné požiadavky

### 1. Administrácia pluginu

Plugin musí vytvoriť vlastnú sekciu v administrácii WordPressu.

Názov menu napríklad:

**Produktové filtre**

Odporúčané podstránky:

1. **Prehľad filtrov**
2. **Nastavenie filtrov**
3. **Farby atribútov**
4. **Nastavenia**

Admin rozhranie má byť jednoduché. Nemá nútiť administrátora ručne vytvárať filtre pre každú kategóriu.

---

## 2. Automatické generovanie filtrov

Plugin musí automaticky generovať filtre z existujúcich WooCommerce dát.

Podporované zdroje filtrov:

- cena produktu
- kategórie produktov
- značky produktov, ak sú dostupné
- WooCommerce atribúty, napríklad `pa_farba`, `pa_velkost`, `pa_material`
- dostupnosť produktu
- výpredaj / produkty v akcii
- hodnotenie produktu
- voliteľne vlastné meta polia produktu

Plugin má pri vykreslení zistiť aktuálny kontext:

- hlavná shop stránka
- produktový archív
- kategória produktu
- značka produktu
- výsledky vyhľadávania produktov

Podľa tohto kontextu má vypísať len tie filtre, ktoré majú zmysel pre aktuálny výpis produktov.

### Pravidlo pre shop / archive

Na hlavnej shop stránke a všeobecnom archíve sa môžu zobrazovať všetky povolené filtre dostupné v e-shope.

Príklad:

- cena
- kategórie
- značky
- farba
- veľkosť
- materiál
- dostupnosť

### Pravidlo pre kategóriu

Na stránke konkrétnej kategórie sa majú filtre odvodiť len z produktov patriacich do danej kategórie.

Príklad:

Ak kategória **Kozmetika** obsahuje produkty s atribútmi:

- počet ihiel
- dĺžka
- materiál
- typ použitia

Plugin zobrazí len tieto relevantné filtre plus všeobecné filtre ako cena a dostupnosť.

Ak iná kategória obsahuje napríklad atribúty:

- farba
- veľkosť
- strih

Plugin zobrazí pre túto kategóriu iné filtre.

---

## 3. Skrytie filtrov

Administrátor musí vedieť skryť ktorýkoľvek automaticky vytvorený filter.

Skrytie musí byť možné:

1. globálne pre celý e-shop
2. len pre konkrétnu kategóriu
3. voliteľne aj pre podradené kategórie

Príklad:

- filter **Značka** sa má zobrazovať globálne
- ale v kategórii **Kozmetika** sa má skryť
- výsledok: v kategórii Kozmetika sa filter Značka nezobrazí, inde áno

Pravidlo skrytia má vždy vyššiu prioritu ako automatické generovanie.

Ak plugin zistí, že filter by sa podľa produktov mal zobraziť, ale administrátor ho skryl, filter sa nesmie vypísať.

---

## 4. Poradie filtrov

Administrátor musí vedieť jednoducho nastaviť poradie filtrov.

Požiadavky:

- drag & drop radenie filtrov v administrácii
- globálne poradie filtrov
- voliteľne možnosť iného poradia pre konkrétnu kategóriu
- ak kategória nemá vlastné poradie, použije sa globálne poradie

Príklad globálneho poradia:

1. Cena
2. Kategórie
3. Značka
4. Farba
5. Veľkosť
6. Materiál

Príklad poradia v kategórii Kozmetika:

1. Cena
2. Počet ihiel
3. Dĺžka
4. Materiál
5. Typ použitia

---

## 5. Typy zobrazenia filtrov

Pri každom automaticky zistenom filtri musí administrátor vedieť nastaviť spôsob zobrazenia.

Podporované typy zobrazenia:

- checkboxy
- radio buttony
- select
- multi-select
- range slider
- input od – do
- farebné štvorčeky / swatches

### Cena

Cena musí podporovať:

- range slider
- input od – do
- automatický min/max podľa dostupných produktov
- voliteľne vlastný min/max rozsah

### Kategórie produktov

Kategórie môžu podporovať:

- checkboxy
- select
- multi-select
- zobraziť len podkategórie aktuálnej kategórie

### Značky produktov

Značky môžu podporovať:

- checkboxy
- select
- multi-select

### Atribúty produktu

WooCommerce atribúty musia podporovať minimálne:

- checkboxy
- radio buttony
- select
- multi-select
- farebné štvorčeky pri atribútoch typu farba
- rozsah od – do, ak ide o číselné hodnoty

---

## 6. Farba ako špeciálny filter

Ak existuje atribút napríklad:

- `pa_farba`
- `pa_color`
- alebo administrátor označí niektorý atribút ako farebný filter

musí byť možné prepnúť jeho zobrazenie na farebné štvorčeky.

V administrácii musí existovať možnosť nastaviť farbu pre každú hodnotu atribútu.

Príklad:

Atribút: **Farba**

Hodnoty:

- Čierna → `#000000`
- Biela → `#ffffff`
- Červená → `#ff0000`
- Modrá → `#0000ff`

Na frontende sa potom filter nezobrazí ako textové checkboxy, ale ako klikateľné farebné štvorčeky.

Požiadavky na farebné štvorčeky:

- musia byť klikateľné
- musia podporovať aktívny stav
- musia mať `title` alebo `aria-label` s názvom hodnoty
- musia byť prístupné aj pre používateľov s klávesnicou
- ak hodnota nemá nastavenú farbu, má sa zobraziť fallback, napríklad textový checkbox alebo neutrálna farba

---

## 7. Globálne nastavenia jednotlivých filtrov

Pre každý automaticky zistený filter musí administrátor vedieť nastaviť:

- či je filter aktívny alebo skrytý
- názov / label filtra
- typ zobrazenia
- poradie
- či má byť filter otvorený alebo zbalený
- či sa majú zobrazovať počty produktov pri hodnotách
- či sa majú skryť prázdne hodnoty
- či sa má filter zobrazovať len vtedy, keď má aspoň 2 dostupné hodnoty

Tieto nastavenia sa ukladajú globálne.

Pre konkrétnu kategóriu musí byť možné prepísať minimálne:

- skryť filter
- zmeniť poradie
- voliteľne zmeniť label
- voliteľne zmeniť typ zobrazenia

Cieľ: čo najjednoduchšie nastavenie bez ručného vytvárania filtrov.

---

## 8. Shortcode

Plugin musí poskytovať shortcode:

```php
[wc_custom_product_filters]
```

Shortcode bez parametrov automaticky zistí aktuálny kontext stránky a zobrazí správne filtre.

Príklad:

Na hlavnej shop stránke zobrazí globálne dostupné filtre.

Na stránke kategórie `kozmetika` zobrazí len filtre odvodené z produktov v kategórii `kozmetika`, pričom zohľadní skryté filtre a poradie nastavené administrátorom.

Shortcode môže podporovať aj voliteľné parametre:

```php
[wc_custom_product_filters context="auto"]
[wc_custom_product_filters category="kozmetika"]
```

Parameter `context="auto"` má byť predvolené správanie.

---

## 9. Frontend správanie

Filtre musia filtrovať WooCommerce produkty na aktuálnej stránke.

Plugin musí podporovať:

- filtrovanie cez URL query parametre
- možnosť zapnúť alebo vypnúť AJAX filtrovanie
- ak je AJAX zapnutý, nesmie sa refreshovať celá stránka, ale majú sa cez AJAX načítať a prepísať iba filtrované produkty
- ak je AJAX vypnutý, filtrovanie funguje klasicky cez reload stránky a URL query parametre
- zachovanie aktuálnej kategórie alebo archívu
- správne fungovanie na shop stránke
- správne fungovanie na kategóriách produktov
- správne fungovanie na stránkach značiek produktov
- správne fungovanie pri vyhľadávaní produktov

Príklad URL:

```text
/kategoria/kozmetika/?filter_min_price=10&filter_max_price=100&filter_pa_pocet-ihiel=12
```

Plugin musí upraviť hlavný WooCommerce product query podľa aktívnych filtrov.

---

## 10. AJAX filtrovanie

V nastaveniach pluginu musí byť možnosť zapnúť alebo vypnúť AJAX filtrovanie.

Ak je AJAX zapnutý:

- po zmene filtra sa nesmie obnoviť celá stránka
- plugin odošle požiadavku cez AJAX
- prepíše sa iba zoznam produktov
- voliteľne sa prepíše aj stránkovanie, počet výsledkov a aktuálne radenie
- URL v prehliadači sa má aktualizovať cez History API, ak je táto možnosť zapnutá

Ak je AJAX vypnutý:

- filter funguje cez klasický reload stránky
- hodnoty filtrov sú v URL parametroch

Nastavenia AJAX-u:

- globálne zapnúť/vypnúť AJAX
- filtrovať automaticky po zmene hodnoty
- filtrovať až po kliknutí na tlačidlo
- CSS selektor kontajnera produktov, napríklad `.products` alebo `.woocommerce ul.products`
- loading stav počas načítavania
- aktualizovať URL v prehliadači áno/nie

---

## 11. Kombinovanie filtrov

Ak používateľ vyberie viac filtrov naraz, plugin musí všetky aktívne hodnoty spojiť do jedného WooCommerce query.

Príklad:

Aktuálna kategória: **Kozmetika**

Vybrané filtre:

- cena od 10 do 100 €
- počet ihiel: 12
- dĺžka: 0.25 mm
- materiál: oceľ

Výsledok:

Zobrazia sa iba produkty v kategórii Kozmetika, ktoré spĺňajú všetky zvolené podmienky.

---

## 12. WPML a Polylang kompatibilita

Plugin musí byť kompatibilný s WPML a Polylang.

Požiadavky:

- všetky texty pluginu musia byť preložiteľné cez `__()`, `_e()`, `esc_html__()` a podobné WordPress funkcie
- plugin musí mať text domain, napríklad `wc-auto-product-filters`
- labely filtrov musia byť preložiteľné
- pri kategóriách, značkách a atribútoch musí plugin správne používať aktuálny jazyk
- pri WPML musí plugin vedieť získať preklad termínu cez WPML funkcie, ak sú dostupné
- pri Polylang musí plugin vedieť získať preklad termínu cez Polylang funkcie, ak sú dostupné
- plugin nesmie natvrdo používať ID kategórií bez kontroly jazykovej mutácie
- pri ukladaní skrytí a kategóriových override nastavení treba rátať s tým, že kategória v jednom jazyku môže mať iné ID ako jej preklad v inom jazyku

Odporúčanie:

Pri ukladaní kategóriových nastavení ukladať primárne term ID a pri vykreslení overiť aktuálny jazyk a nájsť správny preložený term.

---

## 13. Databázová štruktúra

Keďže plugin nemá ručne vytvárať sady filtrov, nie je nutné mať Custom Post Type pre každý filter.

Odporúčaná implementácia:

Použiť jednu alebo viac option položiek vo WordPress options API.

Príklad:

```php
wcapf_global_settings
wcapf_filter_settings
wcapf_category_overrides
wcapf_color_swatches
```

Príklad dát:

```php
wcapf_filter_settings = [
    'price' => [
        'enabled' => true,
        'label' => 'Cena',
        'display_type' => 'range',
        'order' => 10,
    ],
    'pa_farba' => [
        'enabled' => true,
        'label' => 'Farba',
        'display_type' => 'swatches',
        'order' => 20,
    ],
    'pa_material' => [
        'enabled' => true,
        'label' => 'Materiál',
        'display_type' => 'checkbox',
        'order' => 30,
    ],
];
```

Príklad kategóriového override:

```php
wcapf_category_overrides = [
    123 => [
        'hidden_filters' => ['brand', 'pa_velkost'],
        'order' => [
            'price' => 10,
            'pa_pocet-ihiel' => 20,
            'pa_dlzka' => 30,
        ],
    ],
];
```

Alternatíva:

Ak bude potrebné riešiť pokročilé pravidlá alebo výkon na veľkom e-shope, možno neskôr doplniť vlastné databázové tabuľky.

Pre prvú verziu odporúčam použiť WordPress options API.

---

## 14. Admin UI

Admin rozhranie musí byť čo najjednoduchšie.

### Prehľad filtrov

Stránka zobrazí automaticky zistené filtre:

- cena
- kategórie
- značka
- dostupnosť
- akcia
- atribúty produktov

Pri každom filtri bude možné nastaviť:

- zapnutý / skrytý
- názov filtra
- typ zobrazenia
- poradie
- otvorený / zbalený
- zobrazovať počty produktov áno/nie
- skryť prázdne hodnoty áno/nie

### Kategóriové nastavenia

Administrátor si vyberie kategóriu a uvidí filtre, ktoré plugin v danej kategórii deteguje.

Pri každom filtri vie nastaviť:

- zobraziť / skryť v tejto kategórii
- poradie v tejto kategórii
- voliteľne label
- voliteľne typ zobrazenia

### Farby atribútov

Administrátor si vyberie atribút, napríklad **Farba**, a nastaví farby pre hodnoty.

Príklad UI:

| Hodnota | Farba |
|---|---|
| Čierna | color picker `#000000` |
| Biela | color picker `#ffffff` |
| Červená | color picker `#ff0000` |

Použiť WordPress color picker alebo jednoduchý HTML input `type="color"`.

---

## 15. Výkon

Plugin musí byť optimalizovaný.

Požiadavky:

- nenačítavať zbytočné filtre, ktoré sa na aktuálnej stránke nemajú zobrazovať
- pri kategórii zisťovať len atribúty produktov z danej kategórie
- cachovať dostupné filtre pre kategórie pomocou transientov alebo objektovej cache
- cache invalidovať pri uložení produktu, zmene atribútu, zmene kategórie alebo zmene nastavení pluginu
- nepoužívať ťažké query pri každom načítaní stránky, ak to nie je nutné
- escapovať všetky výstupy
- sanitizovať všetky vstupy
- používať WordPress nonce pri ukladaní nastavení

---

## 16. Bezpečnosť

Plugin musí dodržiavať WordPress bezpečnostné štandardy.

Požiadavky:

- kontrola oprávnení cez `current_user_can()`
- nonce ochrana pri ukladaní
- sanitizácia vstupov cez `sanitize_text_field()`, `absint()`, `wc_clean()` a podobne
- escapovanie výstupov cez `esc_html()`, `esc_attr()`, `esc_url()` a podobne
- žiadne priame SQL bez `$wpdb->prepare()`

---

## 17. Súborová štruktúra pluginu

Odporúčaná štruktúra:

```text
wc-auto-product-filters/
│
├── wc-auto-product-filters.php
├── readme.txt
├── languages/
│   └── wc-auto-product-filters.pot
│
├── includes/
│   ├── class-plugin.php
│   ├── class-admin.php
│   ├── class-filter-discovery.php
│   ├── class-shortcode.php
│   ├── class-filter-renderer.php
│   ├── class-query.php
│   ├── class-ajax.php
│   ├── class-compat-wpml.php
│   ├── class-compat-polylang.php
│   └── helpers.php
│
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   └── js/
│       ├── admin.js
│       └── frontend.js
```

---

## 18. Hlavné triedy

### `WC_Auto_Product_Filters_Plugin`

Základná trieda pluginu. Inicializuje všetky ostatné časti.

### `WC_Auto_Product_Filters_Admin`

Rieši administráciu, nastavenia, kategóriové override nastavenia a farby atribútov.

### `WC_Auto_Product_Filters_Discovery`

Automaticky zisťuje dostupné filtre podľa produktov, kategórií, atribútov a aktuálneho kontextu.

### `WC_Auto_Product_Filters_Shortcode`

Registruje shortcode `[wc_custom_product_filters]` a rieši automatický výber filtrov podľa aktuálneho kontextu.

### `WC_Auto_Product_Filters_Renderer`

Generuje HTML výstup filtrov na frontende.

### `WC_Auto_Product_Filters_Query`

Upravuje WooCommerce query podľa aktívnych filtrov.

### `WC_Auto_Product_Filters_Ajax`

Rieši AJAX filtrovanie produktov bez refreshu celej stránky.

### `WC_Auto_Product_Filters_WPML`

Kompatibilita s WPML.

### `WC_Auto_Product_Filters_Polylang`

Kompatibilita s Polylang.

---

## 19. Hooks a integrácia s WooCommerce

Plugin by mal používať najmä tieto hooky:

```php
add_shortcode('wc_custom_product_filters', ...);
add_action('pre_get_posts', ...);
add_filter('woocommerce_product_query_tax_query', ...);
add_filter('woocommerce_product_query_meta_query', ...);
add_action('wp_ajax_wcapf_filter_products', ...);
add_action('wp_ajax_nopriv_wcapf_filter_products', ...);
add_action('wp_enqueue_scripts', ...);
add_action('admin_enqueue_scripts', ...);
```

Pri filtrovaní produktov treba dávať pozor, aby sa úpravy query aplikovali iba na hlavný WooCommerce produktový query a nie na všetky query na stránke.

---

## 20. URL parametre

Plugin musí používať čitateľné a bezpečné URL parametre.

Príklady:

```text
filter_min_price=10
filter_max_price=100
filter_cat=kozmetika
filter_brand=znacka-1
filter_pa_farba=modra
filter_pa_velkost=m
filter_stock=instock
filter_sale=1
```

Pri atribútoch používať prefix podľa WooCommerce taxonómie, napríklad:

```text
filter_pa_pocet-ihiel=12
filter_pa_dlzka=025
```

---

## 21. Reset filtrov

Frontend musí obsahovať možnosť resetovať filtre.

Reset musí odstrániť všetky query parametre, ktoré patria pluginu, ale nemá zmazať dôležité WooCommerce parametre, napríklad radenie, ak sa rozhodne ich zachovať.

---

## 22. Výstup HTML

HTML musí byť jednoduché a ľahko štýlovateľné.

Príklad štruktúry:

```html
<div class="wcapf-filters">
    <form class="wcapf-form" method="get">
        <div class="wcapf-field wcapf-field-price" data-filter="price">
            <button type="button" class="wcapf-title">Cena</button>
            <div class="wcapf-options">
                <input type="number" name="filter_min_price" />
                <input type="number" name="filter_max_price" />
            </div>
        </div>

        <div class="wcapf-field wcapf-field-swatches" data-filter="pa_farba">
            <button type="button" class="wcapf-title">Farba</button>
            <div class="wcapf-options">
                <label class="wcapf-swatch" title="Čierna" aria-label="Čierna">
                    <input type="checkbox" name="filter_pa_farba[]" value="cierna" />
                    <span style="background-color:#000000"></span>
                </label>
            </div>
        </div>

        <button type="submit">Filtrovať</button>
        <a href="..." class="wcapf-reset">Resetovať</a>
    </form>
</div>
```

---

## 23. Požiadavka na prvú verziu MVP

Prvá verzia pluginu musí obsahovať minimálne:

1. vlastné admin menu
2. automatické zistenie filtrov z WooCommerce produktov
3. filtre odvodené podľa aktuálnej kategórie
4. na archíve/shop stránke zobraziť všetky povolené filtre
5. na kategórii zobraziť len filtre relevantné pre danú kategóriu
6. možnosť globálne skryť filter
7. možnosť skryť filter v konkrétnej kategórii
8. možnosť nastaviť poradie filtrov
9. možnosť nastaviť typ zobrazenia filtra: checkbox, select, range
10. možnosť nastaviť atribút farba ako farebné štvorčeky
11. možnosť nastaviť farby pre hodnoty farebného atribútu
12. shortcode `[wc_custom_product_filters]`
13. filtrovanie produktov cez URL parametre
14. globálne zapnutie/vypnutie AJAX filtrovania
15. ak je AJAX zapnutý, filtrované produkty sa prepíšu bez refreshu celej stránky
16. základnú WPML/Polylang pripravenosť
17. bezpečné ukladanie a escapovanie dát

---

## 24. Akceptačné kritériá

Plugin je hotový, keď platí:

- dá sa aktivovať bez PHP chýb
- v administrácii existuje sekcia Produktové filtre
- administrátor nemusí ručne vytvárať filtre od nuly
- plugin automaticky zistí dostupné filtre z produktov
- na shop/archive stránke sa zobrazia všetky povolené filtre
- na stránke kategórie sa zobrazia len filtre odvodené z produktov v tejto kategórii
- administrátor vie filter globálne skryť
- administrátor vie filter skryť len v konkrétnej kategórii
- administrátor vie meniť poradie filtrov
- administrátor vie meniť typ zobrazenia filtra
- atribút Farba sa dá zobraziť ako farebné štvorčeky
- administrátor vie nastaviť farbu pre hodnoty atribútu Farba
- filtrovanie podľa ceny funguje
- filtrovanie podľa kategórie funguje
- filtrovanie podľa atribútov funguje
- URL parametre sa správne aplikujú do WooCommerce query
- AJAX filtrovanie sa dá zapnúť a vypnúť v nastaveniach pluginu
- pri zapnutom AJAX-e sa po filtrovaní neobnoví celá stránka, ale prepíše sa iba výpis produktov
- texty sú pripravené na preklad
- plugin funguje aj pri aktívnom WPML alebo Polylang bez fatálnych chýb

---

## 25. Dôležitá poznámka pre AI vývojára

Naprogramuj plugin modulárne. Nepíš všetko do jedného súboru. Použi objektovo orientovaný prístup, WordPress coding standards a bezpečnostné funkcie WordPressu.

Dôležité: nemeníme koncept na ručne vytvárané sady filtrov. Plugin má filtre automaticky generovať z produktových dát a administrátor má iba jednoducho nastavovať ich viditeľnosť, poradie a typ zobrazenia.

Najprv vytvor MVP verziu s funkčným shortcode, automatickým zistením filtrov, kategóriovým kontextom, cenou, atribútmi, skrytím filtrov, poradím a základným AJAX prepínaním. Až potom rozširuj plugin o pokročilé počítanie produktov, meta polia, detailné override nastavenia a výkonové optimalizácie.

Plugin má byť prakticky použiteľný na reálnom WooCommerce e-shope a má byť čo najjednoduchší pre administrátora.
