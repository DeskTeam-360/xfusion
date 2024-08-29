var userSettings = {
    Layout:  (localStorage.getItem("Layout") !== null)?localStorage.getItem("Layout"): "vertical", // vertical | horizontal
    SidebarType: (localStorage.getItem("SidebarType") !== null)?localStorage.getItem("SidebarType"): "full", // full | mini-sidebar
    BoxedLayout: (localStorage.getItem("BoxedLayout") !== null)? localStorage.getItem("BoxedLayout"):false, // true | false
    Direction: (localStorage.getItem("Direction") !== null)?localStorage.getItem("Direction"): "ltr", // ltr | rtl
    Theme:  (localStorage.getItem("Theme") !== null)? localStorage.getItem("Theme"): "dark", // light | dark
    ColorTheme: (localStorage.getItem("ColorTheme") !== null)?localStorage.getItem("ColorTheme"): "Blue_Theme", // Blue_Theme | Aqua_Theme | Purple_Theme | Green_Theme | Cyan_Theme | Orange_Theme
    cardBorder:  (localStorage.getItem("cardBorder") !== null)?localStorage.getItem("cardBorder"): true, // true | false
};
