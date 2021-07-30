const path = require("path");

const outputFolder = path.resolve(__dirname);
const nodeModules = path.resolve(__dirname, "./node_modules");

// SCSS includePaths
const includePaths = [nodeModules];

module.exports = {
  styles: [
    {
      entry: path.resolve(outputFolder, "resources/sass/default.style.scss"),
      dest: path.resolve(outputFolder, "assets/css/oe_whitelabel.style.min.css"),
      options: {
        includePaths,
        minify: true,
        sourceMap: "file",
      },
    },
  ],
  copy: [
    {
      from: ["node_modules/@openeuropa/bcl-theme-default/css/oe-bcl-default.min.css"],
      to: path.resolve(outputFolder, "assets/css"),
      options: { up: true },
    },
    {
      from: ["node_modules/@openeuropa/bcl-theme-default/css/oe-bcl-default.min.css.map"],
      to: path.resolve(outputFolder, "assets/css"),
      options: { up: true },
    },
    {
      from: ["node_modules/@openeuropa/bcl-theme-default/js/oe-bcl-default.bundle.js"],
      to: path.resolve(outputFolder, "assets/js"),
      options: { up: true },
    }
  ],
};
