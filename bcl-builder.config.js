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
  copy: []
};
